<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminMaintenanceController;
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
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return match (auth()->user()->role) {
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

Route::get('/order/{table:code}', [CustomerOrderController::class, 'show'])->name('customer.menu');
Route::post('/order/{table:code}', [CustomerOrderController::class, 'store'])->name('customer.orders.store');
Route::get('/orders/{order}/status', [CustomerOrderController::class, 'status'])->name('orders.status');
Route::post('/orders/{order}/simulate-payment', [CustomerOrderController::class, 'simulatePayment'])->name('orders.simulate-payment');
Route::post('/orders/{order}/midtrans-token', [MidtransPaymentController::class, 'createSnapToken'])->name('orders.midtrans-token');
Route::post('/orders/{order}/midtrans-sync', [MidtransPaymentController::class, 'syncStatus'])->name('orders.midtrans-sync');
Route::post('/midtrans/notification', [MidtransPaymentController::class, 'notification'])->name('midtrans.notification');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,developer'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/menu', [AdminMenuController::class, 'index'])->name('menu');
    Route::post('/menu', [AdminMenuController::class, 'store'])->name('menu.store');
    Route::patch('/menu/{menuItem}', [AdminMenuController::class, 'update'])->name('menu.update');
    Route::patch('/menu/{menuItem}/toggle', [AdminMenuController::class, 'toggle'])->name('menu.toggle');
    Route::delete('/menu/{menuItem}', [AdminMenuController::class, 'destroy'])->name('menu.destroy');

    Route::get('/tables', [AdminTableController::class, 'index'])->name('tables');
    Route::post('/tables', [AdminTableController::class, 'store'])->name('tables.store');
    Route::patch('/tables/{cafeTable}/toggle', [AdminTableController::class, 'toggle'])->name('tables.toggle');
    Route::get('/tables/qr/print', [AdminTableController::class, 'print'])->name('tables.print');
    Route::get('/tables/{cafeTable}/qr', [AdminTableController::class, 'qr'])->name('tables.qr');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'updatePayment'])->name('orders.payment');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');

    Route::middleware('role:developer')->group(function () {
        Route::get('/maintenance', [AdminMaintenanceController::class, 'index'])->name('maintenance');
        Route::post('/maintenance/users', [AdminMaintenanceController::class, 'storeUser'])->name('maintenance.users.store');
        Route::post('/maintenance/cache/clear', [AdminMaintenanceController::class, 'clearCache'])->name('maintenance.cache.clear');
        Route::get('/maintenance/export-sql', [AdminMaintenanceController::class, 'exportSql'])->name('maintenance.export-sql');
    });
});

Route::prefix('cashier')->name('cashier.')->middleware(['auth', 'role:admin,cashier,developer'])->group(function () {
    Route::get('/orders', [CashierController::class, 'index'])->name('orders');
    Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'updatePayment'])->name('orders.payment');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('/orders/{order}/receipt', [ReceiptController::class, 'show'])->name('orders.receipt');
});

Route::prefix('kitchen')->name('kitchen.')->middleware(['auth', 'role:admin,kitchen,developer'])->group(function () {
    Route::get('/orders', [KitchenController::class, 'index'])->name('orders');
    Route::patch('/orders/{order}/status', [KitchenController::class, 'updateStatus'])->name('orders.status');
});
