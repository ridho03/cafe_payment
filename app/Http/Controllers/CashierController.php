<?php

namespace App\Http\Controllers;

use App\Models\Order;

class CashierController extends Controller
{
    public function index()
    {
        $orders = Order::with(['table', 'items'])
            ->latest()
            ->limit(40)
            ->get();

        $stats = [
            'unpaid' => Order::where('payment_status', 'unpaid')->count(),
            'paid_today' => Order::whereDate('paid_at', today())->where('payment_status', 'paid')->count(),
            'revenue_today' => Order::whereDate('paid_at', today())->where('payment_status', 'paid')->sum('total'),
        ];

        return view('cashier.orders', compact('orders', 'stats'));
    }
}
