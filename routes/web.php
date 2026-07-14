<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminMenuController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminTableController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MidtransPaymentController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return match (auth()->user()->role) {
        'developer', 'super_admin' => redirect()->route('super-admin.dashboard'),
        'cashier' => redirect()->route('cashier.orders'),
        'kitchen' => redirect()->route('kitchen.orders'),
        default => redirect()->route('admin.dashboard'),
    };
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::post('/impersonation/stop', [SuperAdminController::class, 'stopImpersonating'])->middleware('auth')->name('impersonation.stop');

Route::get('/order/{table:code}', [CustomerOrderController::class, 'show'])->name('customer.menu');
Route::post('/order/{table:code}', [CustomerOrderController::class, 'store'])->name('customer.orders.store');
Route::get('/orders/{order}/status', [CustomerOrderController::class, 'status'])->name('orders.status');
Route::post('/orders/{order}/simulate-payment', [CustomerOrderController::class, 'simulatePayment'])->name('orders.simulate-payment');
Route::post('/orders/{order}/midtrans-token', [MidtransPaymentController::class, 'createSnapToken'])->name('orders.midtrans-token');
Route::post('/orders/{order}/midtrans-sync', [MidtransPaymentController::class, 'syncStatus'])->name('orders.midtrans-sync');
Route::post('/midtrans/notification', [MidtransPaymentController::class, 'notification'])->name('midtrans.notification');

Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'role:developer,super_admin'])->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/cafes', [SuperAdminController::class, 'cafes'])->name('cafes');
    Route::post('/cafes', [SuperAdminController::class, 'storeCafe'])->name('cafes.store');
    Route::patch('/cafes/{cafe}', [SuperAdminController::class, 'updateCafe'])->name('cafes.update');
    Route::delete('/cafes/{cafe}', [SuperAdminController::class, 'destroyCafe'])->name('cafes.destroy');
    Route::post('/cafes/{cafe}/impersonate', [SuperAdminController::class, 'impersonateCafe'])->name('cafes.impersonate');

    Route::get('/accounts', [SuperAdminController::class, 'accounts'])->name('accounts');
    Route::post('/accounts', [SuperAdminController::class, 'storeAccount'])->name('accounts.store');
    Route::patch('/accounts/{user}', [SuperAdminController::class, 'updateAccount'])->name('accounts.update');
    Route::patch('/accounts/{user}/password', [SuperAdminController::class, 'resetAccountPassword'])->name('accounts.password');
    Route::delete('/accounts/{user}', [SuperAdminController::class, 'destroyAccount'])->name('accounts.destroy');
    Route::post('/accounts/{user}/impersonate', [SuperAdminController::class, 'impersonateAccount'])->name('accounts.impersonate');

    Route::get('/midtrans', [SuperAdminController::class, 'midtrans'])->name('midtrans');
    Route::patch('/midtrans/{cafe}', [SuperAdminController::class, 'updateMidtrans'])->name('midtrans.update');

    Route::get('/technical', [SuperAdminController::class, 'technical'])->name('technical');
    Route::post('/technical/cache/clear', [SuperAdminController::class, 'clearCache'])->name('technical.cache.clear');
    Route::post('/technical/maintenance', [SuperAdminController::class, 'maintenance'])->name('technical.maintenance');
    Route::get('/technical/export-sql', [SuperAdminController::class, 'exportSql'])->name('technical.export-sql');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/menu', [AdminMenuController::class, 'index'])->name('menu');
    Route::post('/menu/categories', [AdminMenuController::class, 'storeCategory'])->name('menu.categories.store');
    Route::patch('/menu/categories/{menuCategory}', [AdminMenuController::class, 'updateCategory'])->name('menu.categories.update');
    Route::delete('/menu/categories/{menuCategory}', [AdminMenuController::class, 'destroyCategory'])->name('menu.categories.destroy');
    Route::post('/menu', [AdminMenuController::class, 'store'])->name('menu.store');
    Route::patch('/menu/{menuItem}', [AdminMenuController::class, 'update'])->name('menu.update');
    Route::patch('/menu/{menuItem}/toggle', [AdminMenuController::class, 'toggle'])->name('menu.toggle');
    Route::delete('/menu/{menuItem}', [AdminMenuController::class, 'destroy'])->name('menu.destroy');

    Route::get('/tables', [AdminTableController::class, 'index'])->name('tables');
    Route::post('/tables', [AdminTableController::class, 'store'])->name('tables.store');
    Route::patch('/tables/{cafeTable}', [AdminTableController::class, 'update'])->name('tables.update');
    Route::patch('/tables/{cafeTable}/toggle', [AdminTableController::class, 'toggle'])->name('tables.toggle');
    Route::delete('/tables/{cafeTable}', [AdminTableController::class, 'destroy'])->name('tables.destroy');
    Route::get('/tables/qr/print', [AdminTableController::class, 'print'])->name('tables.print');
    Route::get('/tables/{cafeTable}/qr', [AdminTableController::class, 'qr'])->name('tables.qr');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'updatePayment'])->name('orders.payment');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');

});

Route::prefix('cashier')->name('cashier.')->middleware(['auth', 'role:admin,cashier'])->group(function () {
    Route::get('/orders', [CashierController::class, 'index'])->name('orders');
    Route::get('/reports', [CashierController::class, 'reports'])->name('reports');
    Route::get('/reports/export', [CashierController::class, 'exportReports'])->name('reports.export');
    Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'updatePayment'])->name('orders.payment');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('/orders/{order}/receipt', [ReceiptController::class, 'show'])->name('orders.receipt');
});

Route::prefix('kitchen')->name('kitchen.')->middleware(['auth', 'role:admin,kitchen'])->group(function () {
    Route::get('/orders', [KitchenController::class, 'index'])->name('orders');
    Route::patch('/orders/{order}/status', [KitchenController::class, 'updateStatus'])->name('orders.status');
});
