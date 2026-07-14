<?php

namespace Tests\Feature;

use App\Models\CafeTable;
use App\Models\CafeMidtransSetting;
use App\Models\Cafe;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Services\MidtransSnapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_admin_dashboard(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $response = $this->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_home_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_login_page_hides_usage_notes_and_seed_accounts(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('login-coffee.jpg');
        $response->assertDontSee('Catatan penggunaan');
        $response->assertDontSee('Akun awal');
        $response->assertDontSee('admin@payment-cafe.test');
    }

    public function test_admin_dashboard_renders(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Kasir');
        $response->assertSee('data-sidebar-open', false);
        $response->assertSee('data-auto-refresh="8"', false);
        $response->assertSee('Panel operasional');
        $response->assertSee('Aktifkan suara');
    }

    public function test_admin_can_view_and_export_reports(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $table = CafeTable::firstOrFail();

        Order::create([
            'cafe_table_id' => $table->id,
            'code' => 'ORD-REPORT-CASH',
            'subtotal' => 10000,
            'service_fee' => 0,
            'total' => 10000,
            'status' => 'accepted',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        Order::create([
            'cafe_table_id' => $table->id,
            'code' => 'ORD-REPORT-CASHLESS',
            'subtotal' => 20000,
            'service_fee' => 0,
            'total' => 20000,
            'status' => 'accepted',
            'payment_status' => 'paid',
            'payment_method' => 'midtrans_snap',
            'paid_at' => now(),
        ]);

        $response = $this->get(route('admin.reports'));

        $response->assertOk();
        $response->assertSee('Laporan Penjualan');
        $response->assertSee('Export CSV');
        $response->assertSee('Total Cash');
        $response->assertSee('Total Cashless');
        $response->assertSee('Rp 10.000');
        $response->assertSee('Rp 20.000');

        $exportResponse = $this->get(route('admin.reports.export'));

        $exportResponse->assertOk();
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
        $csv = $exportResponse->streamedContent();
        $this->assertStringContainsString('Ringkasan Metode Bayar', $csv);
        $this->assertStringContainsString('Cash,1,10000', $csv);
        $this->assertStringContainsString('Cashless,1,20000', $csv);
        $this->assertStringContainsString('Metode Bayar', $csv);
    }

    public function test_cashier_can_view_and_export_reports(): void
    {
        $this->seed();
        $cashier = User::where('role', 'cashier')->firstOrFail();
        $this->actingAs($cashier);
        $table = CafeTable::where('cafe_id', $cashier->cafe_id)->firstOrFail();

        Order::create([
            'cafe_table_id' => $table->id,
            'code' => 'ORD-CASHIER-CASH',
            'subtotal' => 15000,
            'service_fee' => 0,
            'total' => 15000,
            'status' => 'accepted',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'paid_at' => now(config('app.timezone')),
        ]);

        Order::create([
            'cafe_table_id' => $table->id,
            'code' => 'ORD-CASHIER-CASHLESS',
            'subtotal' => 25000,
            'service_fee' => 0,
            'total' => 25000,
            'status' => 'accepted',
            'payment_status' => 'paid',
            'payment_method' => 'midtrans_snap',
            'paid_at' => now(config('app.timezone')),
        ]);

        $response = $this->get(route('cashier.reports'));

        $response->assertOk();
        $response->assertSee('Laporan Kasir');
        $response->assertSee('Total Cash');
        $response->assertSee('Total Cashless');
        $response->assertSee('Rp 15.000');
        $response->assertSee('Rp 25.000');
        $this->assertSame('Asia/Jakarta', config('app.timezone'));

        $exportResponse = $this->get(route('cashier.reports.export'));

        $exportResponse->assertOk();
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
        $csv = $exportResponse->streamedContent();
        $this->assertStringContainsString('Ringkasan Kasir', $csv);
        $this->assertStringContainsString('Cash,1,15000', $csv);
        $this->assertStringContainsString('Cashless,1,25000', $csv);
        $this->assertStringContainsString('Metode Bayar', $csv);
    }

    public function test_developer_can_create_account_clear_cache_and_export_sql_from_super_admin(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());

        $response = $this->get(route('super-admin.technical'));

        $response->assertOk();
        $response->assertSee('Fitur Teknis');
        $response->assertSee('Export Backup SQL');

        $createResponse = $this->post(route('super-admin.accounts.store'), [
            'cafe_id' => \App\Models\Cafe::firstOrFail()->id,
            'name' => 'Owner Cafe',
            'email' => 'owner@payment-cafe.test',
            'role' => 'admin',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $createResponse->assertRedirect(route('super-admin.accounts'));
        $this->assertDatabaseHas('users', [
            'email' => 'owner@payment-cafe.test',
            'role' => 'admin',
        ]);

        $this->post(route('super-admin.technical.cache.clear'))
            ->assertRedirect(route('super-admin.technical'));

        $exportResponse = $this->get(route('super-admin.technical.export-sql'));

        $exportResponse->assertOk();
        $this->assertStringContainsString('application/sql', $exportResponse->headers->get('content-type'));
    }

    public function test_maintenance_keeps_super_admin_open_and_blocks_operational_panels(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());

        try {
            $response = $this->post(route('super-admin.technical.maintenance'), [
                'enabled' => '1',
            ]);

            $response->assertRedirect(route('super-admin.technical'));
            $response->assertCookieMissing('laravel_maintenance');

            $this->get(route('super-admin.technical'))
                ->assertOk()
                ->assertSee('Maintenance mode')
                ->assertSee('Aktif');

            $this->get('/')->assertRedirect(route('super-admin.dashboard'));

            auth()->logout();

            $this->get('/')->assertRedirect(route('login'));
            $this->get(route('login'))->assertOk();

            $this->get(route('admin.dashboard'))->assertStatus(503);
            $this->get(route('cashier.orders'))->assertStatus(503);
            $this->get(route('kitchen.orders'))->assertStatus(503);
        } finally {
            Artisan::call('up');
        }
    }

    public function test_guest_is_redirected_to_login_from_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_seeded_admin_can_login(): void
    {
        $this->seed();

        $response = $this->post(route('login.store'), [
            'email' => 'admin@payment-cafe.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_seeded_developer_can_login_and_open_all_internal_panels(): void
    {
        $this->seed();
        $developer = $this->developerUser();

        $response = $this->post(route('login.store'), [
            'email' => $developer->email,
            'password' => 'developer-password',
        ]);

        $response->assertRedirect(route('super-admin.dashboard'));
        $this->assertAuthenticated();

        $this->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Super Admin')
            ->assertSee('data-auto-refresh="15"', false)
            ->assertDontSee('Dashboard Cafe')
            ->assertDontSee('Admin Pesanan')
            ->assertDontSee('Meja QR');
        $this->get(route('super-admin.cafes'))->assertOk()->assertSee('Kelola Cafe');
        $this->get(route('super-admin.accounts'))->assertOk()->assertSee('Manajemen Akun');
        $this->get(route('super-admin.midtrans'))->assertOk()->assertSee('Pengaturan Midtrans');
        $this->get(route('super-admin.technical'))->assertOk()->assertSee('Fitur Teknis');
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('cashier.orders'))->assertForbidden();
        $this->get(route('kitchen.orders'))->assertForbidden();
    }

    public function test_admin_cafe_cannot_open_super_admin_pages(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $this->get(route('super-admin.dashboard'))->assertForbidden();
        $this->get(route('super-admin.cafes'))->assertForbidden();
        $this->get(route('super-admin.accounts'))->assertForbidden();
        $this->get(route('super-admin.midtrans'))->assertForbidden();
        $this->get(route('super-admin.technical'))->assertForbidden();
    }

    public function test_super_admin_can_store_midtrans_keys_encrypted_and_masked(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());
        $cafe = \App\Models\Cafe::firstOrFail();

        $response = $this->patch(route('super-admin.midtrans.update', $cafe), [
            'mode' => 'sandbox',
            'merchant_id' => 'G123456789',
            'client_key' => 'SB-Mid-client-test-123456',
            'server_key' => 'SB-Mid-server-test-abcdef',
            'is_integrated' => '1',
        ]);

        $response->assertRedirect(route('super-admin.midtrans'));

        $setting = CafeMidtransSetting::where('cafe_id', $cafe->id)->firstOrFail();
        $this->assertSame('SB-Mid-server-test-abcdef', $setting->server_key);
        $this->assertDatabaseMissing('cafe_midtrans_settings', [
            'server_key' => 'SB-Mid-server-test-abcdef',
        ]);

        $this->get(route('super-admin.midtrans'))
            ->assertOk()
            ->assertSee($setting->maskedServerKey())
            ->assertDontSee('SB-Mid-server-test-abcdef');
    }

    public function test_super_admin_can_replace_unreadable_midtrans_keys(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());
        $cafe = Cafe::firstOrFail();

        $setting = CafeMidtransSetting::create([
            'cafe_id' => $cafe->id,
            'mode' => 'sandbox',
            'merchant_id' => 'G123456789',
            'client_key' => 'old-client-key',
            'server_key' => 'old-server-key',
            'is_integrated' => true,
        ]);

        DB::table('cafe_midtrans_settings')
            ->where('id', $setting->id)
            ->update([
                'client_key' => 'encrypted-with-another-app-key',
                'server_key' => 'also-not-readable',
            ]);

        $this->get(route('super-admin.midtrans'))
            ->assertOk()
            ->assertSee('Perlu input ulang');

        $response = $this->patch(route('super-admin.midtrans.update', $cafe), [
            'mode' => 'production',
            'merchant_id' => 'G987654321',
            'client_key' => 'Mid-client-fresh',
            'server_key' => 'Mid-server-fresh',
            'is_integrated' => '1',
        ]);

        $response->assertRedirect(route('super-admin.midtrans'));

        $freshSetting = CafeMidtransSetting::where('cafe_id', $cafe->id)->firstOrFail();
        $this->assertSame('Mid-client-fresh', $freshSetting->client_key);
        $this->assertSame('Mid-server-fresh', $freshSetting->server_key);
        $this->assertTrue($freshSetting->isReady());
    }

    public function test_super_admin_can_impersonate_admin_and_return(): void
    {
        $this->seed();
        $developer = $this->developerUser();
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($developer);

        $this->post(route('super-admin.accounts.impersonate', $admin))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Mode cek cafe aktif')
            ->assertSee('Kembali ke Super Admin');

        $this->get(route('super-admin.dashboard'))->assertForbidden();

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('super-admin.dashboard'));

        $this->assertAuthenticatedAs($developer);
    }

    public function test_super_admin_can_create_cafe_with_template(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());

        $this->get(route('super-admin.cafes'))
            ->assertOk()
            ->assertSee('Warung Makan')
            ->assertSee('Bakery')
            ->assertSee('Boba &amp; Dessert', false);

        $response = $this->post(route('super-admin.cafes.store'), [
            'name' => 'Template Coffee Test',
            'slug' => 'template-coffee-test',
            'status' => 'active',
            'active_from' => now()->toDateString(),
            'template_key' => 'coffee_shop',
        ]);

        $response->assertRedirect(route('super-admin.cafes'));

        $cafe = Cafe::where('slug', 'template-coffee-test')->firstOrFail();

        $this->assertSame(3, MenuCategory::where('cafe_id', $cafe->id)->count());
        $this->assertSame(9, MenuItem::whereHas('category', fn ($query) => $query->where('cafe_id', $cafe->id))->count());
        $this->assertSame(6, CafeTable::where('cafe_id', $cafe->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'cafe_id' => $cafe->id,
            'action' => 'cafe.created',
        ]);
    }

    public function test_super_admin_can_create_additional_cafe_templates(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());

        $this->post(route('super-admin.cafes.store'), [
            'name' => 'Warung Template Test',
            'slug' => 'warung-template-test',
            'status' => 'active',
            'template_key' => 'warung_makan',
        ])->assertRedirect(route('super-admin.cafes'));

        $warung = Cafe::where('slug', 'warung-template-test')->firstOrFail();

        $this->assertSame(3, MenuCategory::where('cafe_id', $warung->id)->count());
        $this->assertSame(12, MenuItem::whereHas('category', fn ($query) => $query->where('cafe_id', $warung->id))->count());
        $this->assertSame(8, CafeTable::where('cafe_id', $warung->id)->count());
        $this->assertDatabaseHas('menu_items', ['name' => 'Nasi Ayam Geprek']);

        $this->post(route('super-admin.cafes.store'), [
            'name' => 'Bakery Template Test',
            'slug' => 'bakery-template-test',
            'status' => 'active',
            'template_key' => 'bakery',
        ])->assertRedirect(route('super-admin.cafes'));

        $bakery = Cafe::where('slug', 'bakery-template-test')->firstOrFail();

        $this->assertSame(3, MenuCategory::where('cafe_id', $bakery->id)->count());
        $this->assertSame(10, MenuItem::whereHas('category', fn ($query) => $query->where('cafe_id', $bakery->id))->count());
        $this->assertSame(5, CafeTable::where('cafe_id', $bakery->id)->count());
        $this->assertDatabaseHas('menu_items', ['name' => 'Butter Croissant']);
    }

    public function test_super_admin_can_create_blank_cafe_template(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());

        $this->post(route('super-admin.cafes.store'), [
            'name' => 'Cafe Kosong Test',
            'slug' => 'cafe-kosong-test',
            'status' => 'active',
            'template_key' => 'blank',
        ])->assertRedirect(route('super-admin.cafes'));

        $cafe = Cafe::where('slug', 'cafe-kosong-test')->firstOrFail();

        $this->assertSame(0, MenuCategory::where('cafe_id', $cafe->id)->count());
        $this->assertSame(0, CafeTable::where('cafe_id', $cafe->id)->count());
    }

    public function test_super_admin_can_delete_accounts_and_empty_cafes_but_not_cafes_with_orders(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());

        $cafe = Cafe::create([
            'name' => 'Cafe CRUD Delete',
            'slug' => 'cafe-crud-delete',
            'status' => 'active',
            'active_from' => now()->toDateString(),
        ]);

        $account = User::create([
            'name' => 'Admin Delete',
            'email' => 'admin-delete@payment-cafe.test',
            'role' => 'admin',
            'cafe_id' => $cafe->id,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->delete(route('super-admin.accounts.destroy', $account))
            ->assertRedirect(route('super-admin.accounts'));

        $this->assertDatabaseMissing('users', [
            'id' => $account->id,
        ]);

        $category = MenuCategory::create([
            'cafe_id' => $cafe->id,
            'name' => 'Kategori Hapus',
            'sort_order' => 10,
        ]);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Menu Hapus',
            'price' => 20000,
            'is_available' => true,
            'sort_order' => 10,
        ]);
        $table = CafeTable::create([
            'cafe_id' => $cafe->id,
            'name' => 'Meja Hapus',
            'code' => 'MEJA-HAPUS-CRUD',
            'capacity' => 2,
            'is_active' => true,
        ]);
        CafeMidtransSetting::create([
            'cafe_id' => $cafe->id,
            'mode' => 'sandbox',
            'is_integrated' => false,
        ]);

        $this->delete(route('super-admin.cafes.destroy', $cafe))
            ->assertRedirect(route('super-admin.cafes'));

        $this->assertDatabaseMissing('cafes', ['id' => $cafe->id]);
        $this->assertDatabaseMissing('menu_categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('cafe_tables', ['id' => $table->id]);
        $this->assertDatabaseMissing('cafe_midtrans_settings', ['cafe_id' => $cafe->id]);

        $protectedCafe = Cafe::firstOrFail();
        $protectedTable = CafeTable::where('cafe_id', $protectedCafe->id)->firstOrFail();

        Order::create([
            'cafe_table_id' => $protectedTable->id,
            'code' => 'ORD-PROTECT-CAFE',
            'subtotal' => 10000,
            'service_fee' => 0,
            'total' => 10000,
            'status' => 'new',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
        ]);

        $this->delete(route('super-admin.cafes.destroy', $protectedCafe))
            ->assertStatus(422);

        $this->assertDatabaseHas('cafes', ['id' => $protectedCafe->id]);
    }

    public function test_cafe_name_from_super_admin_appears_on_panel_menu_qr_and_receipt(): void
    {
        $this->seed();

        $cafe = Cafe::firstOrFail();
        $cafe->update([
            'name' => 'Kopi Panjang Nusantara',
            'address' => 'Jl. Nama Cafe Sangat Panjang Nomor 123',
            'contact_phone' => '0812-3456-7890',
        ]);

        $admin = User::where('role', 'admin')->firstOrFail();
        $admin->update(['cafe_id' => $cafe->id]);
        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kopi Panjang Nusantara');

        $table = CafeTable::firstOrFail();
        $table->update(['cafe_id' => $cafe->id]);

        $this->get(route('customer.menu', ['table' => $table->code]))
            ->assertOk()
            ->assertSee('Menu Kopi Panjang Nusantara');

        $this->get(route('admin.tables.print'))
            ->assertOk()
            ->assertSee('Kopi Panjang Nusantara');

        $item = MenuItem::where('is_available', true)->firstOrFail();
        $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'cash',
            'items' => [
                $item->id => 1,
            ],
        ]);

        $order = Order::latest()->firstOrFail();

        $this->get(route('cashier.orders.receipt', $order))
            ->assertOk()
            ->assertSee('Kopi Panjang Nusantara')
            ->assertSee('Jl. Nama Cafe Sangat Panjang Nomor 123')
            ->assertSee('0812-3456-7890');
    }

    public function test_admin_data_is_separated_between_cafes(): void
    {
        $this->seed();

        $oldCafe = Cafe::firstOrFail();
        $newCafe = Cafe::create([
            'name' => 'Cafe Baru Mandiri',
            'slug' => 'cafe-baru-mandiri',
            'status' => 'active',
            'active_from' => now()->toDateString(),
        ]);

        $newAdmin = User::create([
            'name' => 'Admin Baru',
            'email' => 'admin-baru@payment-cafe.test',
            'role' => 'admin',
            'cafe_id' => $newCafe->id,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $newTable = CafeTable::create([
            'cafe_id' => $newCafe->id,
            'name' => 'Meja Cafe Baru',
            'code' => 'MEJA-BARU-01',
            'capacity' => 2,
            'is_active' => true,
        ]);

        $newCategory = MenuCategory::create([
            'cafe_id' => $newCafe->id,
            'name' => 'Menu Cafe Baru',
            'sort_order' => 10,
        ]);

        MenuItem::create([
            'menu_category_id' => $newCategory->id,
            'name' => 'Latte Cafe Baru',
            'price' => 30000,
            'is_available' => true,
            'sort_order' => 10,
        ]);

        $oldTable = CafeTable::where('cafe_id', $oldCafe->id)->firstOrFail();
        $oldItem = MenuItem::whereHas('category', fn ($query) => $query->where('cafe_id', $oldCafe->id))->firstOrFail();
        $oldOrder = Order::create([
            'cafe_table_id' => $oldTable->id,
            'code' => 'ORD-OLD-CAFE',
            'subtotal' => $oldItem->price,
            'service_fee' => 0,
            'total' => $oldItem->price,
            'status' => 'new',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
        ]);
        $oldOrder->items()->create([
            'menu_item_id' => $oldItem->id,
            'name_snapshot' => $oldItem->name,
            'price_snapshot' => $oldItem->price,
            'quantity' => 1,
            'total' => $oldItem->price,
        ]);

        $this->actingAs($newAdmin);

        $this->get(route('admin.tables'))
            ->assertOk()
            ->assertSee('Meja Cafe Baru')
            ->assertDontSee($oldTable->code);

        $this->get(route('admin.menu'))
            ->assertOk()
            ->assertSee('Latte Cafe Baru')
            ->assertDontSee($oldItem->name);

        $this->get(route('admin.orders'))
            ->assertOk()
            ->assertDontSee('ORD-OLD-CAFE');

        $this->get(route('customer.menu', ['table' => $newTable->code]))
            ->assertOk()
            ->assertSee('Latte Cafe Baru')
            ->assertDontSee($oldItem->name);
    }

    public function test_customer_status_uses_midtrans_setting_from_order_cafe(): void
    {
        $this->seed();

        $cafe = Cafe::firstOrFail();
        CafeMidtransSetting::updateOrCreate(
            ['cafe_id' => $cafe->id],
            [
                'mode' => 'sandbox',
                'merchant_id' => 'G123456789',
                'client_key' => 'SB-Mid-client-cafe',
                'server_key' => 'SB-Mid-server-cafe',
                'is_integrated' => true,
            ]
        );

        $table = CafeTable::where('cafe_id', $cafe->id)->firstOrFail();
        $item = MenuItem::whereHas('category', fn ($query) => $query->where('cafe_id', $cafe->id))->firstOrFail();

        $this->mock(MidtransSnapService::class, function ($mock) {
            $mock->shouldReceive('ensureSnapToken')
                ->once()
                ->andReturnUsing(function (Order $order): void {
                    $order->update([
                        'midtrans_order_id' => $order->code,
                        'midtrans_snap_token' => 'snap-token-test',
                    ]);
                });
        });

        $response = $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'midtrans_snap',
            'items' => [
                $item->id => 1,
            ],
        ]);

        $response->assertRedirect();
        $order = Order::latest()->firstOrFail();

        $this->get(route('orders.status', $order))
            ->assertOk()
            ->assertSee('Bayar Cashless')
            ->assertDontSee('data-auto-open-midtrans', false)
            ->assertDontSee('Siapkan Pembayaran')
            ->assertDontSee('file .env');
    }

    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('email', 'admin@payment-cafe.test')->count());
        $this->assertSame(1, CafeTable::where('code', 'MEJA-01')->count());
        $this->assertSame(1, MenuCategory::where('name', 'Coffee')->count());
    }

    public function test_seeded_cashier_can_login_to_cashier_screen(): void
    {
        $this->seed();

        $response = $this->post(route('login.store'), [
            'email' => 'kasir@payment-cafe.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('cashier.orders'));
        $this->assertAuthenticated();
    }

    public function test_seeded_kitchen_can_login_to_kitchen_screen(): void
    {
        $this->seed();

        $response = $this->post(route('login.store'), [
            'email' => 'dapur@payment-cafe.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('kitchen.orders'));
        $this->assertAuthenticated();
    }

    public function test_customer_can_open_menu_from_table_code(): void
    {
        $this->seed();

        $table = CafeTable::firstOrFail();

        $response = $this->get(route('customer.menu', ['table' => $table->code]));

        $response->assertOk();
        $response->assertSee($table->name);
        $response->assertSee('Buat Pesanan');
        $response->assertSee('Rincian, nama, dan catatan');
        $response->assertSee('data-auto-refresh="0"', false);
    }

    public function test_admin_menu_renders_custom_variant_controls(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $item = MenuItem::where('is_available', true)->firstOrFail();
        $item->update(['variants' => ['hot', 'Large']]);

        $response = $this->get(route('admin.menu'));

        $response->assertOk();
        $response->assertSee('Grup variasi tambahan');
        $response->assertSee('Large');
    }

    public function test_admin_can_manage_menu_categories_and_tables_with_crud_safeguards(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin);

        $this->post(route('admin.menu.categories.store'), [
            'name' => 'Seasonal',
            'sort_order' => 90,
        ])->assertRedirect(route('admin.menu'));

        $category = MenuCategory::where('cafe_id', $admin->cafe_id)
            ->where('name', 'Seasonal')
            ->firstOrFail();

        $this->patch(route('admin.menu.categories.update', $category), [
            'name' => 'Seasonal Drinks',
            'sort_order' => 95,
        ])->assertRedirect(route('admin.menu'));

        $this->assertDatabaseHas('menu_categories', [
            'id' => $category->id,
            'name' => 'Seasonal Drinks',
            'sort_order' => 95,
        ]);

        $this->delete(route('admin.menu.categories.destroy', $category->fresh()))
            ->assertRedirect(route('admin.menu'));

        $this->assertDatabaseMissing('menu_categories', [
            'id' => $category->id,
        ]);

        $protectedCategory = MenuCategory::where('cafe_id', $admin->cafe_id)->has('items')->firstOrFail();

        $this->delete(route('admin.menu.categories.destroy', $protectedCategory))
            ->assertStatus(422);

        $this->post(route('admin.tables.store'), [
            'name' => 'Meja CRUD',
            'capacity' => 4,
        ])->assertRedirect(route('admin.tables'));

        $table = CafeTable::where('cafe_id', $admin->cafe_id)
            ->where('name', 'Meja CRUD')
            ->firstOrFail();

        $this->patch(route('admin.tables.update', $table), [
            'name' => 'Meja CRUD Edit',
            'capacity' => 6,
        ])->assertRedirect(route('admin.tables'));

        $this->assertDatabaseHas('cafe_tables', [
            'id' => $table->id,
            'name' => 'Meja CRUD Edit',
            'capacity' => 6,
        ]);

        $this->delete(route('admin.tables.destroy', $table->fresh()))
            ->assertRedirect(route('admin.tables'));

        $this->assertDatabaseMissing('cafe_tables', [
            'id' => $table->id,
        ]);

        $protectedTable = CafeTable::where('cafe_id', $admin->cafe_id)->firstOrFail();

        Order::create([
            'cafe_table_id' => $protectedTable->id,
            'code' => 'ORD-PROTECT-TABLE',
            'subtotal' => 10000,
            'service_fee' => 0,
            'total' => 10000,
            'status' => 'new',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
        ]);

        $this->delete(route('admin.tables.destroy', $protectedTable))
            ->assertStatus(422);

        $this->assertDatabaseHas('cafe_tables', ['id' => $protectedTable->id]);
    }

    public function test_admin_can_open_printable_qr_sheet(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $response = $this->get(route('admin.tables.print'));

        $response->assertOk();
        $response->assertSee('Cetak QR Meja');
        $response->assertSee('Kartu meja 4/A4');
        $response->assertSee('Label kecil 8/A4');
        $response->assertSee('Scan Menu');
        $response->assertSee('Arahkan kamera ke QR');
        $response->assertSee('data-layout="card"', false);
        $response->assertSee('@page { size: A4 portrait; margin: 0; }', false);
    }

    public function test_admin_can_open_compact_printable_qr_labels(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $response = $this->get(route('admin.tables.print', ['layout' => 'label']));

        $response->assertOk();
        $response->assertSee('data-layout="label"', false);
        $response->assertSee('Label kecil 8/A4');
    }

    public function test_sensitive_feature_urls_use_public_ids_instead_of_numeric_ids(): void
    {
        $this->seed();

        $developer = $this->developerUser();
        $admin = User::where('role', 'admin')->firstOrFail();
        $cafe = Cafe::firstOrFail();
        $table = CafeTable::firstOrFail();
        $category = MenuCategory::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();

        $this->actingAs($developer);
        $this->assertRouteUsesPublicId(route('super-admin.cafes.update', $cafe), $cafe);
        $this->assertRouteUsesPublicId(route('super-admin.cafes.destroy', $cafe), $cafe);
        $this->assertRouteUsesPublicId(route('super-admin.accounts.update', $admin), $admin);
        $this->assertRouteUsesPublicId(route('super-admin.accounts.password', $admin), $admin);
        $this->assertRouteUsesPublicId(route('super-admin.accounts.destroy', $admin), $admin);
        $this->assertRouteUsesPublicId(route('super-admin.accounts.impersonate', $admin), $admin);

        $this->actingAs($admin);
        $this->assertRouteUsesPublicId(route('admin.menu.categories.update', $category), $category);
        $this->assertRouteUsesPublicId(route('admin.menu.categories.destroy', $category), $category);
        $this->assertRouteUsesPublicId(route('admin.tables.update', $table), $table);
        $this->assertRouteUsesPublicId(route('admin.tables.destroy', $table), $table);
        $this->assertRouteUsesPublicId(route('admin.tables.qr', $table), $table);
        $this->assertRouteUsesPublicId(route('admin.menu.update', $item), $item);
        $this->assertRouteUsesPublicId(route('admin.menu.destroy', $item), $item);

        $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'cash',
            'items' => [
                $item->id => 1,
            ],
        ]);

        $order = Order::latest()->firstOrFail();

        $this->assertRouteUsesPublicId(route('orders.status', $order), $order);
        $this->assertRouteUsesPublicId(route('cashier.orders.receipt', $order), $order);
        $this->assertRouteUsesPublicId(route('kitchen.orders.status', $order), $order);
    }

    public function test_customer_can_place_order(): void
    {
        $this->seed();

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();
        $item->update(['variants' => ['hot', 'ice', 'Large']]);

        $response = $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'cash',
            'customer_name' => 'Budi',
            'items' => [
                $item->id => 2,
            ],
            'item_variants' => [
                $item->id => 'Large',
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'cafe_table_id' => $table->id,
            'customer_name' => 'Budi',
            'payment_status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('order_items', [
            'menu_item_id' => $item->id,
            'variant' => 'Suhu: Hot, Pilihan: Large',
            'quantity' => 2,
        ]);

        $order = Order::latest()->firstOrFail();
        $statusResponse = $this->get(route('orders.status', $order));
        $statusResponse->assertOk();
        $statusResponse->assertSee('data-auto-refresh="4"', false);
    }

    public function test_customer_must_choose_payment_method_before_checkout(): void
    {
        $this->seed();

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();

        $this->from(route('customer.menu', ['table' => $table->code]))
            ->post(route('customer.orders.store', ['table' => $table->code]), [
                'items' => [
                    $item->id => 1,
                ],
            ])
            ->assertRedirect(route('customer.menu', ['table' => $table->code]))
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseMissing('orders', [
            'cafe_table_id' => $table->id,
        ]);
    }

    public function test_customer_can_choose_multiple_variant_groups_with_price_delta(): void
    {
        $this->seed();

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();
        $item->update([
            'price' => 20000,
            'variants' => MenuItem::normalizeVariantGroups(
                ['hot', 'ice'],
                'Ukuran: Regular, Large=5000'
            ),
        ]);

        $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'cash',
            'items' => [
                $item->id => 2,
            ],
            'item_variants' => [
                $item->id => [
                    0 => 'ice',
                    1 => 'Large',
                ],
            ],
        ])->assertRedirect();

        $order = Order::latest()->firstOrFail();

        $this->assertSame(50000, $order->subtotal);
        $this->assertDatabaseHas('order_items', [
            'menu_item_id' => $item->id,
            'variant' => 'Suhu: Ice, Ukuran: Large',
            'price_snapshot' => 25000,
            'quantity' => 2,
            'total' => 50000,
        ]);
    }

    public function test_admin_can_create_update_and_delete_menu_with_uploaded_photo(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        File::deleteDirectory(public_path('uploads/menu'));

        $category = MenuCategory::firstOrFail();

        $createResponse = $this->post(route('admin.menu.store'), [
            'menu_category_id' => $category->id,
            'name' => 'Manual Brew',
            'description' => 'Kopi filter harian',
            'price' => 28000,
            'variants' => ['hot', 'ice'],
            'custom_variants' => 'Large, Less Sugar, large',
            'image_upload' => $this->tinyPngUpload('manual-brew.png'),
        ]);

        $createResponse->assertRedirect(route('admin.menu'));
        $item = MenuItem::where('name', 'Manual Brew')->firstOrFail();
        $this->assertStringContainsString('uploads/menu/', $item->image_url);
        $this->assertSame(['hot', 'ice', 'Large', 'Less Sugar'], $item->availableVariants());
        $this->assertTrue(File::exists(public_path(ltrim(parse_url($item->image_url, PHP_URL_PATH), '/'))));

        $updateResponse = $this->patch(route('admin.menu.update', $item), [
            'menu_category_id' => $category->id,
            'name' => 'Manual Brew V60',
            'description' => 'Kopi filter single origin',
            'price' => 32000,
        ]);

        $updateResponse->assertRedirect(route('admin.menu'));
        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'name' => 'Manual Brew V60',
            'price' => 32000,
        ]);
        $item->refresh();
        $this->assertStringContainsString('uploads/menu/', $item->image_url);
        $this->assertSame(['hot', 'ice', 'Large', 'Less Sugar'], $item->availableVariants());
        $this->assertTrue(File::exists(public_path(ltrim(parse_url($item->image_url, PHP_URL_PATH), '/'))));

        $deleteResponse = $this->delete(route('admin.menu.destroy', $item->fresh()));

        $deleteResponse->assertRedirect(route('admin.menu'));
        $this->assertDatabaseMissing('menu_items', [
            'id' => $item->id,
        ]);
        $this->assertFalse(File::exists(public_path(ltrim(parse_url($item->image_url, PHP_URL_PATH), '/'))));
    }

    private function tinyPngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'menu-test-');

        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function developerUser(): User
    {
        return User::updateOrCreate(
            ['email' => 'developer@payment-cafe.test'],
            [
                'name' => 'Developer Test',
                'role' => 'developer',
                'password' => Hash::make('developer-password'),
            ]
        );
    }

    private function assertRouteUsesPublicId(string $url, $model): void
    {
        $segments = explode('/', trim(parse_url($url, PHP_URL_PATH) ?: '', '/'));

        $this->assertNotEmpty($model->public_id);
        $this->assertContains($model->public_id, $segments);
        $this->assertNotContains((string) $model->id, $segments);
    }

    public function test_cashier_can_open_receipt_for_order(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'cashier')->firstOrFail());

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();

        $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'cash',
            'customer_name' => 'Budi',
            'items' => [
                $item->id => 1,
            ],
        ]);

        $order = Order::latest()->firstOrFail();

        $response = $this->get(route('cashier.orders.receipt', $order));

        $response->assertOk();
        $response->assertSee($order->code);
        $response->assertSee('Cetak');
    }

    public function test_receipt_can_render_thermal_58mm_size(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'cashier')->firstOrFail());

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();

        $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'cash',
            'items' => [
                $item->id => 1,
            ],
        ]);

        $order = Order::latest()->firstOrFail();

        $response = $this->get(route('cashier.orders.receipt', ['order' => $order, 'paper' => 58]));

        $response->assertOk();
        $response->assertSee('@page { size: 58mm auto; margin: 0; }', false);
        $response->assertSee('--receipt-width: 58mm;', false);
    }

    public function test_kitchen_can_move_paid_order_to_preparing(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'kitchen')->firstOrFail());

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();

        $this->post(route('customer.orders.store', ['table' => $table->code]), [
            'payment_method' => 'cash',
            'items' => [
                $item->id => 1,
            ],
        ]);

        $order = Order::latest()->firstOrFail();
        $order->update([
            'payment_status' => 'paid',
            'status' => 'accepted',
            'paid_at' => now(),
        ]);

        $response = $this->patch(route('kitchen.orders.status', $order), [
            'status' => 'preparing',
        ]);

        $response->assertRedirect(route('kitchen.orders'));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'preparing',
        ]);
    }
}
