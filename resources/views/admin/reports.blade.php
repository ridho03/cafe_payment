@extends('layouts.app')

@section('title', 'Laporan Penjualan')
@section('auto_refresh', '60')

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format((int) $amount, 0, ',', '.');
    $maxDailyRevenue = max((int) $dailySales->max('revenue'), 1);
@endphp

<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Rekap bisnis</p>
            <h1 class="pc-title">Laporan Penjualan</h1>
            <p class="pc-subtle mt-2">
                {{ $from->format('d M Y') }} sampai {{ $to->format('d M Y') }}
            </p>
        </div>
        <a href="{{ route('admin.reports.export', request()->only(['from', 'to'])) }}" class="pc-button-secondary">
            Export CSV
        </a>
    </div>

    <section class="pc-card mt-6 p-4">
        <form method="GET" action="{{ route('admin.reports') }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <label class="pc-label">
                Dari tanggal
                <input name="from" type="date" value="{{ $from->toDateString() }}" class="pc-input">
            </label>
            <label class="pc-label">
                Sampai tanggal
                <input name="to" type="date" value="{{ $to->toDateString() }}" class="pc-input">
            </label>
            <button class="pc-button-primary min-h-12">
                Terapkan
            </button>
        </form>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="Ringkasan laporan">
        <div class="pc-stat">
            <p class="pc-subtle">Omzet lunas</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700">{{ $format($summary['revenue']) }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Order lunas</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $summary['paid_orders'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Total order</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-800">{{ $summary['orders'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Item terjual</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $summary['items_sold'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Rata-rata order</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $format($summary['average_order']) }}</p>
        </div>
    </section>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1.25fr_0.75fr]">
        <section class="pc-card overflow-hidden">
            <div class="pc-table-head px-4 py-3">
                <h2 class="font-bold text-stone-950">Tren omzet harian</h2>
            </div>
            <div class="space-y-3 p-4">
                @forelse ($dailySales as $day)
                    @php
                        $width = max(6, ((int) $day->revenue / $maxDailyRevenue) * 100);
                    @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                            <span class="font-bold text-stone-700">{{ \Carbon\CarbonImmutable::parse($day->sale_date)->format('d M') }}</span>
                            <span class="font-extrabold tabular-nums text-stone-950">{{ $format($day->revenue) }}</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-amber-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-stone-950 via-amber-800 to-emerald-700" style="width: {{ $width }}%"></div>
                        </div>
                        <p class="mt-1 text-xs font-semibold text-stone-500">{{ $day->orders_count }} order lunas</p>
                    </div>
                @empty
                    <p class="text-sm font-semibold text-stone-500">Belum ada transaksi lunas pada rentang tanggal ini.</p>
                @endforelse
            </div>
        </section>

        <section class="pc-card overflow-hidden">
            <div class="pc-table-head px-4 py-3">
                <h2 class="font-bold text-stone-950">Menu terlaris</h2>
            </div>
            <div class="divide-y divide-amber-100">
                @forelse ($topItems as $item)
                    <div class="flex items-start justify-between gap-3 p-4">
                        <div>
                            <p class="font-bold text-stone-950">{{ $item->name_snapshot }}</p>
                            <p class="pc-subtle mt-1">{{ $item->quantity }} item terjual</p>
                        </div>
                        <p class="font-extrabold tabular-nums text-emerald-700">{{ $format($item->revenue) }}</p>
                    </div>
                @empty
                    <p class="p-4 text-sm font-semibold text-stone-500">Menu terlaris akan muncul setelah ada transaksi lunas.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="pc-card overflow-hidden">
            <div class="pc-table-head px-4 py-3">
                <h2 class="font-bold text-stone-950">Status pesanan</h2>
            </div>
            <div class="divide-y divide-amber-100">
                @forelse ($statusBreakdown as $status)
                    <div class="flex items-center justify-between gap-3 p-4">
                        <span class="pc-badge {{ (new \App\Models\Order(['status' => $status->status]))->statusBadgeClass() }}">
                            {{ \App\Models\Order::STATUS_FLOW[$status->status] ?? ucfirst($status->status) }}
                        </span>
                        <span class="font-extrabold tabular-nums text-stone-950">{{ $status->total }}</span>
                    </div>
                @empty
                    <p class="p-4 text-sm font-semibold text-stone-500">Belum ada data status.</p>
                @endforelse
            </div>
        </section>

        <section class="pc-card overflow-hidden">
            <div class="pc-table-head px-4 py-3">
                <h2 class="font-bold text-stone-950">Status pembayaran</h2>
            </div>
            <div class="divide-y divide-amber-100">
                @forelse ($paymentBreakdown as $payment)
                    <div class="flex items-center justify-between gap-3 p-4">
                        <span class="pc-badge {{ (new \App\Models\Order(['payment_status' => $payment->payment_status]))->paymentBadgeClass() }}">
                            {{ \App\Models\Order::PAYMENT_FLOW[$payment->payment_status] ?? ucfirst($payment->payment_status) }}
                        </span>
                        <span class="font-extrabold tabular-nums text-stone-950">{{ $payment->total }}</span>
                    </div>
                @empty
                    <p class="p-4 text-sm font-semibold text-stone-500">Belum ada data pembayaran.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
