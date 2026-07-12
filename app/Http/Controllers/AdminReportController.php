<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $baseOrders = Order::query()->whereBetween('created_at', [$from, $to]);
        $paidOrders = (clone $baseOrders)->where('payment_status', 'paid');

        $summary = [
            'orders' => (clone $baseOrders)->count(),
            'paid_orders' => (clone $paidOrders)->count(),
            'revenue' => (clone $paidOrders)->sum('total'),
            'subtotal' => (clone $paidOrders)->sum('subtotal'),
            'service_fee' => (clone $paidOrders)->sum('service_fee'),
            'items_sold' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereBetween('orders.created_at', [$from, $to])
                ->where('orders.payment_status', 'paid')
                ->sum('order_items.quantity'),
        ];
        $summary['average_order'] = $summary['paid_orders'] > 0
            ? (int) round($summary['revenue'] / $summary['paid_orders'])
            : 0;

        $statusBreakdown = (clone $baseOrders)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $paymentBreakdown = (clone $baseOrders)
            ->select('payment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_status')
            ->orderByDesc('total')
            ->get();

        $dailySales = (clone $paidOrders)
            ->select(DB::raw('DATE(created_at) as sale_date'), DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        $topItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select(
                'order_items.name_snapshot',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.total) as revenue')
            )
            ->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.payment_status', 'paid')
            ->groupBy('order_items.name_snapshot')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        return view('admin.reports', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'statusBreakdown' => $statusBreakdown,
            'paymentBreakdown' => $paymentBreakdown,
            'dailySales' => $dailySales,
            'topItems' => $topItems,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $filename = 'laporan-payment-cafe-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($from, $to) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Kode', 'Tanggal', 'Meja', 'Pelanggan', 'Status', 'Pembayaran', 'Subtotal', 'Layanan', 'Total']);

            Order::with('table')
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->chunk(200, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->code,
                            $order->created_at->format('Y-m-d H:i:s'),
                            $order->table?->name,
                            $order->customer_name,
                            $order->statusLabel(),
                            $order->paymentLabel(),
                            $order->subtotal,
                            $order->service_fee,
                            $order->total,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($validated['to'] ?? now()->toDateString())->endOfDay();

        return [$from, $to];
    }
}
