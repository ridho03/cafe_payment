<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashierController extends Controller
{
    public function index()
    {
        $cafeId = $this->currentCafeId();
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $endOfToday = $today->endOfDay();

        $orders = Order::with(['table', 'items'])
            ->whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))
            ->latest()
            ->limit(40)
            ->get();

        $stats = [
            'unpaid' => Order::whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))->where('payment_status', 'unpaid')->count(),
            'paid_today' => Order::whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))->whereBetween('paid_at', [$today, $endOfToday])->where('payment_status', 'paid')->count(),
            'revenue_today' => Order::whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))->whereBetween('paid_at', [$today, $endOfToday])->where('payment_status', 'paid')->sum('total'),
        ];

        return view('cashier.orders', compact('orders', 'stats'));
    }

    public function reports(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $cafeId = $this->currentCafeId();

        $baseOrders = $this->ordersInRange($cafeId, $from, $to);
        $paidOrders = $this->paidOrdersInRange($cafeId, $from, $to);

        $cashOrders = (clone $paidOrders)->where('payment_method', 'cash');
        $cashlessOrders = (clone $paidOrders)->where('payment_method', '!=', 'cash');

        $summary = [
            'orders' => (clone $baseOrders)->count(),
            'paid_orders' => (clone $paidOrders)->count(),
            'unpaid_orders' => (clone $baseOrders)->where('payment_status', 'unpaid')->count(),
            'revenue' => (clone $paidOrders)->sum('total'),
            'cash_revenue' => (clone $cashOrders)->sum('total'),
            'cash_orders' => (clone $cashOrders)->count(),
            'cashless_revenue' => (clone $cashlessOrders)->sum('total'),
            'cashless_orders' => (clone $cashlessOrders)->count(),
        ];
        $summary['average_order'] = $summary['paid_orders'] > 0
            ? (int) round($summary['revenue'] / $summary['paid_orders'])
            : 0;

        $orders = (clone $baseOrders)
            ->with('table')
            ->latest()
            ->limit(60)
            ->get();

        return view('cashier.reports', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'orders' => $orders,
        ]);
    }

    public function exportReports(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $cafeId = $this->currentCafeId();
        $filename = 'laporan-kasir-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($from, $to, $cafeId) {
            $handle = fopen('php://output', 'w');
            $paidOrders = $this->paidOrdersInRange($cafeId, $from, $to);

            $cashRevenue = (clone $paidOrders)->where('payment_method', 'cash')->sum('total');
            $cashlessRevenue = (clone $paidOrders)->where('payment_method', '!=', 'cash')->sum('total');
            $cashOrders = (clone $paidOrders)->where('payment_method', 'cash')->count();
            $cashlessOrders = (clone $paidOrders)->where('payment_method', '!=', 'cash')->count();

            fputcsv($handle, ['Ringkasan Kasir']);
            fputcsv($handle, ['Periode', $from->format('Y-m-d H:i:s').' - '.$to->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['Metode', 'Order Lunas', 'Total']);
            fputcsv($handle, ['Cash', $cashOrders, $cashRevenue]);
            fputcsv($handle, ['Cashless', $cashlessOrders, $cashlessRevenue]);
            fputcsv($handle, []);
            fputcsv($handle, ['Kode', 'Tanggal', 'Meja', 'Pelanggan', 'Status', 'Pembayaran', 'Metode Bayar', 'Total']);

            $this->ordersInRange($cafeId, $from, $to)
                ->with('table')
                ->orderBy('created_at')
                ->chunk(200, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        $transactionTime = ($order->paid_at ?? $order->created_at)->timezone(config('app.timezone'));

                        fputcsv($handle, [
                            $order->code,
                            $transactionTime->format('Y-m-d H:i:s'),
                            $order->table?->name,
                            $order->customer_name,
                            $order->statusLabel(),
                            $order->paymentLabel(),
                            $order->paymentMethodLabel(),
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

        $timezone = config('app.timezone');
        $from = CarbonImmutable::parse($validated['from'] ?? now($timezone)->toDateString(), $timezone)->startOfDay();
        $to = CarbonImmutable::parse($validated['to'] ?? now($timezone)->toDateString(), $timezone)->endOfDay();

        return [$from, $to];
    }

    private function ordersInRange(int $cafeId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return Order::query()
            ->whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))
            ->where(function ($query) use ($from, $to) {
                $query
                    ->whereBetween('created_at', [$from, $to])
                    ->orWhereBetween('paid_at', [$from, $to]);
            });
    }

    private function paidOrdersInRange(int $cafeId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return Order::query()
            ->whereHas('table', fn ($query) => $query->where('cafe_id', $cafeId))
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$from, $to]);
    }
}
