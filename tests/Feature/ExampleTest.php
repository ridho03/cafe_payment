<?php

namespace Tests\Feature;

use App\Models\CafeTable;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_login_page_hides_usage_notes_and_demo_accounts(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('login-coffee.jpg');
        $response->assertDontSee('Catatan penggunaan');
        $response->assertDontSee('Akun demo');
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
        $response->assertSee('data-auto-refresh="30"', false);
        $response->assertSee('Panel operasional');
        $response->assertSee('Aktifkan suara');
    }

    public function test_admin_can_view_and_export_reports(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $response = $this->get(route('admin.reports'));

        $response->assertOk();
        $response->assertSee('Laporan Penjualan');
        $response->assertSee('Export CSV');

        $exportResponse = $this->get(route('admin.reports.export'));

        $exportResponse->assertOk();
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
    }

    public function test_admin_cafe_cannot_open_developer_maintenance(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $response = $this->get(route('admin.maintenance'));

        $response->assertForbidden();
    }

    public function test_developer_can_open_maintenance_create_user_and_export_sql(): void
    {
        $this->seed();
        $this->actingAs($this->developerUser());

        $response = $this->get(route('admin.maintenance'));

        $response->assertOk();
        $response->assertSee('Maintenance Aplikasi');
        $response->assertSee('Export SQL');
        $response->assertSee('Developer/Penyedia');

        $createResponse = $this->post(route('admin.maintenance.users.store'), [
            'name' => 'Owner Cafe',
            'email' => 'owner@payment-cafe.test',
            'role' => 'admin',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $createResponse->assertRedirect(route('admin.maintenance'));
        $this->assertDatabaseHas('users', [
            'email' => 'owner@payment-cafe.test',
            'role' => 'admin',
        ]);

        $exportResponse = $this->get(route('admin.maintenance.export-sql'));

        $exportResponse->assertOk();
        $this->assertStringContainsString('application/sql', $exportResponse->headers->get('content-type'));
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

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.maintenance'))->assertOk();
        $this->get(route('cashier.orders'))->assertOk();
        $this->get(route('kitchen.orders'))->assertOk();
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
        $response->assertSee('data-auto-refresh="45"', false);
    }

    public function test_admin_menu_renders_custom_variant_controls(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'admin')->firstOrFail());

        $item = MenuItem::where('is_available', true)->firstOrFail();
        $item->update(['variants' => ['hot', 'Large']]);

        $response = $this->get(route('admin.menu'));

        $response->assertOk();
        $response->assertSee('Varian tambahan');
        $response->assertSee('Large');
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
        $response->assertSee('Scan untuk pesan');
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

    public function test_customer_can_place_order(): void
    {
        $this->seed();

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();
        $item->update(['variants' => ['hot', 'ice', 'Large']]);

        $response = $this->post(route('customer.orders.store', ['table' => $table->code]), [
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
            'variant' => 'Large',
            'quantity' => 2,
        ]);

        $order = Order::latest()->firstOrFail();
        $statusResponse = $this->get(route('orders.status', $order));
        $statusResponse->assertOk();
        $statusResponse->assertSee('data-auto-refresh="10"', false);
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
        $this->assertSame(['hot', 'ice', 'Large', 'Less Sugar'], $item->variants);
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
        $this->assertSame(['hot', 'ice', 'Large', 'Less Sugar'], $item->variants);
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

    public function test_cashier_can_open_receipt_for_order(): void
    {
        $this->seed();
        $this->actingAs(User::where('role', 'cashier')->firstOrFail());

        $table = CafeTable::firstOrFail();
        $item = MenuItem::where('is_available', true)->firstOrFail();

        $this->post(route('customer.orders.store', ['table' => $table->code]), [
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
