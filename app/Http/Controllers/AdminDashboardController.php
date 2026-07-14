<?php

namespace App\Http\Controllers;

use App\Models\Cafe;
use App\Models\CafeTable;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke()
    {
        $cafeId = $this->currentCafeId();
        $currentCafe = $cafeId ? Cafe::find($cafeId) : null;

        $orders = Order::with(['table', 'items'])
            ->whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))
            ->latest()
            ->limit(8)
            ->get();

        $stats = [
            'today_orders' => Order::whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))->whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))->whereDate('created_at', today())->where('payment_status', 'paid')->sum('total'),
            'open_orders' => Order::whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'active_tables' => CafeTable::where('cafe_id', $cafeId)->where('is_active', true)->count(),
            'available_items' => MenuItem::whereHas('category', fn ($query) => $query->where('cafe_id', $cafeId))->where('is_available', true)->count(),
        ];

        $popularItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('cafe_tables', 'cafe_tables.id', '=', 'orders.cafe_table_id')
            ->where('cafe_tables.cafe_id', $cafeId)
            ->select('name_snapshot', DB::raw('SUM(quantity) as sold'))
            ->groupBy('name_snapshot')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('orders', 'stats', 'popularItems', 'currentCafe'));
    }
}
