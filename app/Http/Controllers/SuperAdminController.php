<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Cafe;
use App\Models\CafeMidtransSetting;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Services\CafeTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminController extends Controller
{
    public function __construct(private readonly CafeTemplateService $templates)
    {
    }

    public function dashboard()
    {
        $cafes = Cafe::with('midtransSetting')->withCount('users')->latest()->get();
        $usersByRole = User::query()
            ->whereIn('role', ['admin', 'cashier', 'kitchen'])
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $stats = [
            'cafes' => Cafe::count(),
            'admins' => (int) ($usersByRole['admin'] ?? 0),
            'cashiers' => (int) ($usersByRole['cashier'] ?? 0),
            'kitchens' => (int) ($usersByRole['kitchen'] ?? 0),
            'transactions' => Order::count(),
            'midtrans_integrated' => CafeMidtransSetting::where('is_integrated', true)->count(),
            'expiring' => $cafes->filter(fn (Cafe $cafe) => $cafe->expiresSoon())->count(),
            'expired' => $cafes->filter(fn (Cafe $cafe) => $cafe->isPastActiveUntil() || $cafe->status === 'expired')->count(),
        ];

        $expiringCafes = $cafes
            ->filter(fn (Cafe $cafe) => $cafe->expiresSoon() || $cafe->isPastActiveUntil() || $cafe->status === 'expired')
            ->sortBy(fn (Cafe $cafe) => $cafe->daysUntilExpired() ?? 9999)
            ->take(6);

        $cafeStatuses = Cafe::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $system = $this->systemInfo();

        return view('super-admin.dashboard', compact('cafes', 'stats', 'cafeStatuses', 'system', 'expiringCafes'));
    }

    public function cafes()
    {
        $cafes = Cafe::with(['midtransSetting'])
            ->withCount(['users', 'tables', 'menuCategories', 'orders'])
            ->latest()
            ->get();

        return view('super-admin.cafes', [
            'cafes' => $cafes,
            'statuses' => Cafe::STATUSES,
            'templates' => $this->templates->templates(),
        ]);
    }

    public function storeCafe(Request $request)
    {
        $validated = $this->validateCafe($request);
        $templateKey = $validated['template_key'] ?? 'coffee_shop';
        $validated['slug'] = $this->uniqueCafeSlug($validated['slug'] ?: $validated['name']);
        $validated['logo_path'] = $this->storeLogo($request);
        unset($validated['logo'], $validated['remove_logo'], $validated['template_key']);

        $cafe = Cafe::create($validated);
        $templateResult = $this->templates->apply($cafe, $templateKey);

        $this->audit('cafe.created', 'Cafe baru dibuat: '.$cafe->name, $cafe, [
            'template' => $templateKey,
            'categories' => $templateResult['categories'],
            'items' => $templateResult['items'],
            'tables' => $templateResult['tables'],
        ]);

        return redirect()->route('super-admin.cafes')->with('success', 'Cafe berhasil ditambahkan dengan template '.$this->templates->templates()[$templateKey]['name'].'.');
    }

    public function updateCafe(Request $request, Cafe $cafe)
    {
        $validated = $this->validateCafe($request, $cafe);
        $validated['slug'] = $this->uniqueCafeSlug($validated['slug'] ?: $validated['name'], $cafe);

        if ($request->hasFile('logo')) {
            $this->deleteLogo($cafe->logo_path);
            $validated['logo_path'] = $this->storeLogo($request);
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($cafe->logo_path);
            $validated['logo_path'] = null;
        }

        unset($validated['logo'], $validated['remove_logo']);

        $cafe->update($validated);

        $this->audit('cafe.updated', 'Cafe diperbarui: '.$cafe->name, $cafe);

        return redirect()->route('super-admin.cafes')->with('success', 'Cafe berhasil diperbarui.');
    }

    public function destroyCafe(Cafe $cafe)
    {
        $cafe->loadCount(['users', 'tables', 'menuCategories', 'orders']);

        abort_if($cafe->orders_count > 0, 422, 'Cafe sudah memiliki transaksi. Ubah status menjadi Suspend/Expired agar histori tetap aman.');

        $name = $cafe->name;
        $counts = [
            'users' => $cafe->users_count,
            'tables' => $cafe->tables_count,
            'categories' => $cafe->menu_categories_count,
        ];

        MenuItem::whereHas('category', fn ($query) => $query->where('cafe_id', $cafe->id))
            ->pluck('image_url')
            ->each(fn (?string $imageUrl) => $this->deleteUploadedMenuImage($imageUrl));

        $this->deleteLogo($cafe->logo_path);

        DB::transaction(function () use ($cafe, $name, $counts): void {
            $this->audit('cafe.deleted', 'Cafe dihapus: '.$name, $cafe, $counts);

            $cafe->users()->whereIn('role', ['admin', 'cashier', 'kitchen'])->delete();
            $cafe->midtransSetting()->delete();
            $cafe->menuCategories()->delete();
            $cafe->tables()->delete();
            $cafe->delete();
        });

        return redirect()->route('super-admin.cafes')->with('success', 'Cafe berhasil dihapus.');
    }

    public function impersonateCafe(Cafe $cafe)
    {
        $user = $cafe->users()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $user) {
            return redirect()
                ->route('super-admin.cafes')
                ->withErrors('Cafe ini belum punya akun Admin Cafe aktif.');
        }

        return $this->startImpersonation($user);
    }

    public function accounts()
    {
        $cafes = Cafe::orderBy('name')->get();
        $users = User::with('cafe')
            ->whereIn('role', ['admin', 'cashier', 'kitchen'])
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'cashier' THEN 2 WHEN 'kitchen' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();

        return view('super-admin.accounts', compact('cafes', 'users'));
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'cafe_id' => ['required', 'exists:cafes,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'cashier', 'kitchen'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'cafe_id' => $validated['cafe_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active', true),
            'password' => Hash::make($validated['password']),
        ]);

        $this->audit('account.created', 'Akun dibuat: '.$user->email, $user->cafe);

        return redirect()->route('super-admin.accounts')->with('success', 'Akun berhasil dibuat.');
    }

    public function updateAccount(Request $request, User $user)
    {
        abort_if($user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'cafe_id' => ['required', 'exists:cafes,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'cashier', 'kitchen'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'cafe_id' => $validated['cafe_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->audit('account.updated', 'Akun diperbarui: '.$user->email, $user->cafe);

        return redirect()->route('super-admin.accounts')->with('success', 'Akun berhasil diperbarui.');
    }

    public function resetAccountPassword(Request $request, User $user)
    {
        abort_if($user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        $this->audit('account.password_reset', 'Password akun direset: '.$user->email, $user->cafe);

        return redirect()->route('super-admin.accounts')->with('success', 'Password akun berhasil direset.');
    }

    public function destroyAccount(User $user)
    {
        abort_if($user->isSuperAdmin(), 403);

        $user->load('cafe');
        $email = $user->email;
        $cafe = $user->cafe;

        $this->audit('account.deleted', 'Akun dihapus: '.$email, $cafe);

        $user->delete();

        return redirect()->route('super-admin.accounts')->with('success', 'Akun berhasil dihapus.');
    }

    public function impersonateAccount(User $user)
    {
        abort_if($user->isSuperAdmin(), 403);
        abort_unless($user->is_active, 422, 'Akun ini sedang nonaktif.');

        return $this->startImpersonation($user);
    }

    public function stopImpersonating(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        abort_unless($impersonatorId, 403);

        $impersonator = User::whereKey($impersonatorId)->whereIn('role', ['developer', 'super_admin'])->firstOrFail();
        Auth::login($impersonator);
        $request->session()->regenerate();

        return redirect()->route('super-admin.dashboard')->with('success', 'Kembali ke akun Super Admin.');
    }

    public function midtrans()
    {
        $cafes = Cafe::with('midtransSetting')->orderBy('name')->get();

        return view('super-admin.midtrans', compact('cafes'));
    }

    public function updateMidtrans(Request $request, Cafe $cafe)
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['sandbox', 'production'])],
            'merchant_id' => ['nullable', 'string', 'max:120'],
            'client_key' => ['nullable', 'string', 'max:500'],
            'server_key' => ['nullable', 'string', 'max:500'],
            'is_integrated' => ['nullable', 'boolean'],
            'clear_keys' => ['nullable', 'boolean'],
        ]);

        $setting = $cafe->midtransSetting ?: new CafeMidtransSetting(['cafe_id' => $cafe->id]);
        $setting->mode = $validated['mode'];
        $setting->merchant_id = $validated['merchant_id'] ?? null;
        $setting->is_integrated = $request->boolean('is_integrated');
        $setting->last_checked_at = now();

        if ($request->boolean('clear_keys')) {
            $setting->client_key = null;
            $setting->server_key = null;
        } else {
            if (filled($validated['client_key'] ?? null)) {
                $setting->client_key = $validated['client_key'];
            }

            if (filled($validated['server_key'] ?? null)) {
                $setting->server_key = $validated['server_key'];
            }
        }

        $setting->save();

        $this->audit('midtrans.updated', 'Konfigurasi Midtrans diperbarui: '.$cafe->name, $cafe, [
            'mode' => $setting->mode,
            'is_integrated' => $setting->is_integrated,
            'keys_updated' => filled($validated['client_key'] ?? null) || filled($validated['server_key'] ?? null),
            'keys_cleared' => $request->boolean('clear_keys'),
        ]);

        return redirect()->route('super-admin.midtrans')->with('success', 'Pengaturan Midtrans berhasil disimpan.');
    }

    public function technical()
    {
        return view('super-admin.technical', [
            'system' => $this->systemInfo(),
            'auditLogs' => AuditLog::with(['user', 'cafe'])->latest()->limit(40)->get(),
            'isDown' => app()->isDownForMaintenance(),
        ]);
    }

    public function clearCache()
    {
        Artisan::call('optimize:clear');

        $this->audit('system.cache_cleared', 'Cache aplikasi dibersihkan.');

        return redirect()->route('super-admin.technical')->with('success', 'Cache aplikasi berhasil dibersihkan.');
    }

    public function maintenance(Request $request)
    {
        if ($request->boolean('enabled')) {
            Artisan::call('down');
            $message = 'Maintenance mode diaktifkan. Panel super admin tetap bisa diakses.';
            $this->audit('system.maintenance_enabled', 'Maintenance mode diaktifkan.');
        } else {
            Artisan::call('up');
            $message = 'Maintenance mode dinonaktifkan.';
            $this->audit('system.maintenance_disabled', 'Maintenance mode dinonaktifkan.');
        }

        return redirect()->route('super-admin.technical')->with('success', $message);
    }

    public function exportSql(): StreamedResponse
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $driver = $connection->getDriverName();
        $filename = 'backup-'.$database.'-'.now()->format('Ymd-His').'.sql';

        $this->audit('system.backup_exported', 'Backup SQL diexport.');

        return response()->streamDownload(function () use ($connection, $database, $driver) {
            echo "-- Backup ".config('app.name').PHP_EOL;
            echo "-- Database: {$database}".PHP_EOL;
            echo "-- Generated: ".now()->toDateTimeString().PHP_EOL.PHP_EOL;

            match ($driver) {
                'mysql', 'mariadb' => $this->streamMysqlDump($connection),
                'sqlite' => $this->streamSqliteDump($connection),
                default => print "-- Driver {$driver} belum didukung untuk export SQL.".PHP_EOL,
            };
        }, $filename, ['Content-Type' => 'application/sql; charset=UTF-8']);
    }

    private function validateCafe(Request $request, ?Cafe $cafe = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('cafes', 'slug')->ignore($cafe?->id)],
            'logo' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'subdomain' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys(Cafe::STATUSES))],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'template_key' => [$cafe ? 'nullable' : 'required', Rule::in($this->templates->keys())],
        ]);
    }

    private function startImpersonation(User $user)
    {
        $impersonatorId = auth()->id();

        $this->audit('account.impersonated', 'Super Admin masuk sebagai akun: '.$user->email, $user->cafe);

        session(['impersonator_id' => $impersonatorId]);
        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route(match ($user->role) {
            'cashier' => 'cashier.orders',
            'kitchen' => 'kitchen.orders',
            default => 'admin.dashboard',
        })->with('success', 'Sedang masuk sebagai '.$user->name.'.');
    }

    private function storeLogo(Request $request): ?string
    {
        if (! $request->hasFile('logo')) {
            return null;
        }

        $directory = public_path('uploads/cafes');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file = $request->file('logo');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/cafes/'.$filename;
    }

    private function uniqueCafeSlug(string $value, ?Cafe $ignore = null): string
    {
        $base = Str::slug($value) ?: 'cafe';
        $slug = $base;
        $suffix = 2;

        while (Cafe::where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function deleteLogo(?string $logoPath): void
    {
        if (! filled($logoPath) || ! str_starts_with($logoPath, 'uploads/cafes/')) {
            return;
        }

        $fullPath = public_path($logoPath);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function deleteUploadedMenuImage(?string $imageUrl): void
    {
        if (! filled($imageUrl) || ! str_starts_with($imageUrl, 'uploads/menu/')) {
            return;
        }

        $fullPath = public_path($imageUrl);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function systemInfo(): array
    {
        return [
            'Aplikasi' => config('app.name'),
            'Environment' => app()->environment(),
            'Debug' => config('app.debug') ? 'Aktif' : 'Nonaktif',
            'PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Database' => config('database.default').' / '.DB::connection()->getDatabaseName(),
            'Cache' => config('cache.default'),
            'Queue' => config('queue.default'),
            'Server' => request()->server('SERVER_SOFTWARE', PHP_SAPI),
        ];
    }

    private function audit(string $action, string $description, ?Cafe $cafe = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'cafe_id' => $cafe?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 500, ''),
            'metadata' => $metadata ?: null,
        ]);
    }

    private function streamMysqlDump($connection): void
    {
        echo 'SET FOREIGN_KEY_CHECKS=0;'.PHP_EOL.PHP_EOL;

        $tables = collect($connection->select('SHOW FULL TABLES'))
            ->map(fn ($row) => array_values((array) $row))
            ->filter(fn ($row) => ($row[1] ?? null) === 'BASE TABLE')
            ->map(fn ($row) => $row[0])
            ->values();

        foreach ($tables as $table) {
            $quotedTable = '`'.str_replace('`', '``', $table).'`';
            $create = (array) $connection->selectOne("SHOW CREATE TABLE {$quotedTable}");
            $createSql = $create['Create Table'] ?? array_values($create)[1] ?? '';

            echo "DROP TABLE IF EXISTS {$quotedTable};".PHP_EOL;
            echo $createSql.';'.PHP_EOL.PHP_EOL;

            $this->streamTableRows($connection, $table, $quotedTable);
        }

        echo 'SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL;
    }

    private function streamSqliteDump($connection): void
    {
        $tables = collect($connection->select("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name"));

        foreach ($tables as $table) {
            $name = $table->name;
            $quotedTable = '"'.str_replace('"', '""', $name).'"';

            echo "DROP TABLE IF EXISTS {$quotedTable};".PHP_EOL;
            echo $table->sql.';'.PHP_EOL.PHP_EOL;

            $this->streamTableRows($connection, $name, $quotedTable);
        }
    }

    private function streamTableRows($connection, string $table, string $quotedTable): void
    {
        $rows = [];
        $columns = null;

        foreach ($connection->table($table)->cursor() as $row) {
            $data = (array) $row;
            $columns ??= collect(array_keys($data))
                ->map(fn ($column) => '`'.str_replace('`', '``', $column).'`')
                ->implode(', ');
            $values = collect(array_values($data))
                ->map(fn ($value) => $this->sqlValue($connection, $value))
                ->implode(', ');

            $rows[] = "({$values})";

            if (count($rows) >= 100) {
                echo "INSERT INTO {$quotedTable} ({$columns}) VALUES".PHP_EOL.implode(','.PHP_EOL, $rows).';'.PHP_EOL;
                $rows = [];
            }
        }

        if ($rows !== []) {
            echo "INSERT INTO {$quotedTable} ({$columns}) VALUES".PHP_EOL.implode(','.PHP_EOL, $rows).';'.PHP_EOL;
        }

        echo PHP_EOL;
    }

    private function sqlValue($connection, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $connection->getPdo()->quote((string) $value);
    }
}
