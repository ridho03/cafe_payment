<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminMaintenanceController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->orderByRaw("CASE role WHEN 'developer' THEN 1 WHEN 'admin' THEN 2 WHEN 'cashier' THEN 3 WHEN 'kitchen' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->get();

        $system = [
            'app_name' => config('app.name'),
            'app_env' => app()->environment(),
            'debug' => config('app.debug') ? 'Aktif' : 'Nonaktif',
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'database' => config('database.default').' / '.DB::connection()->getDatabaseName(),
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
        ];

        return view('admin.maintenance', compact('users', 'system'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['developer', 'admin', 'cashier', 'kitchen'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.maintenance')->with('success', 'User baru berhasil dibuat.');
    }

    public function clearCache()
    {
        Artisan::call('optimize:clear');

        return redirect()->route('admin.maintenance')->with('success', 'Cache aplikasi berhasil dibersihkan.');
    }

    public function exportSql(): StreamedResponse
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $driver = $connection->getDriverName();
        $filename = 'backup-'.$database.'-'.now()->format('Ymd-His').'.sql';

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
