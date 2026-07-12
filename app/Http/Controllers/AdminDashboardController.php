<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke()
    {
        $orders = Order::with(['table', 'items'])
            ->latest()
            ->limit(8)
            ->get();

        $stats = [
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())->where('payment_status', 'paid')->sum('total'),
            'open_orders' => Order::whereNotIn('status', ['completed', 'cancelled'])->count(),
            'active_tables' => CafeTable::where('is_active', true)->count(),
            'available_items' => MenuItem::where('is_available', true)->count(),
        ];

        $popularItems = DB::table('order_items')
            ->select('name_snapshot', DB::raw('SUM(quantity) as sold'))
            ->groupBy('name_snapshot')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('orders', 'stats', 'popularItems'));
    }
}
