@extends('layouts.app')

@section('title', 'Laporan Kasir')
@section('auto_refresh', '60')

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format((int) $amount, 0, ',', '.');
@endphp

<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Rekap pembayaran</p>
            <h1 class="pc-title">Laporan Kasir</h1>
            <p class="pc-subtle mt-2">
                {{ $from->format('d M Y') }} sampai {{ $to->format('d M Y') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cashier.orders') }}" class="pc-button-secondary">
                Kasir
            </a>
            <a href="{{ route('cashier.reports.export', request()->only(['from', 'to'])) }}" class="pc-button-secondary">
                Export CSV
            </a>
        </div>
    </div>

    <section class="pc-card mt-6 p-4">
        <form method="GET" action="{{ route('cashier.reports') }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
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

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan laporan kasir">
        <div class="pc-stat">
            <p class="pc-subtle">Omzet lunas</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700">{{ $format($summary['revenue']) }}</p>
            <p class="mt-1 text-xs font-bold text-stone-500">{{ $summary['paid_orders'] }} order lunas</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Total Cash</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $format($summary['cash_revenue']) }}</p>
            <p class="mt-1 text-xs font-bold text-stone-500">{{ $summary['cash_orders'] }} order lunas</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Total Cashless</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700">{{ $format($summary['cashless_revenue']) }}</p>
            <p class="mt-1 text-xs font-bold text-stone-500">{{ $summary['cashless_orders'] }} order lunas</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Belum bayar</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-700">{{ $summary['unpaid_orders'] }}</p>
            <p class="mt-1 text-xs font-bold text-stone-500">{{ $summary['orders'] }} total order</p>
        </div>
    </section>

    <section class="pc-card mt-5 overflow-hidden">
        <div class="pc-table-head grid grid-cols-[1fr_130px_140px_130px] gap-3 px-4 py-3 max-lg:hidden">
            <span>Transaksi</span>
            <span>Pembayaran</span>
            <span>Metode</span>
            <span class="text-right">Total</span>
        </div>
        <div class="divide-y divide-amber-100">
            @forelse ($orders as $order)
                <article class="grid gap-3 p-4 lg:grid-cols-[1fr_130px_140px_130px] lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-stone-950">{{ $order->code }}</h2>
                            <span class="pc-badge border border-amber-200 bg-amber-50 text-amber-950">{{ $order->table?->name }}</span>
                            <span class="pc-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                        </div>
                        <p class="pc-subtle mt-1">
                            {{ ($order->paid_at ?? $order->created_at)->timezone(config('app.timezone'))->format('d M Y H:i') }}
                            &middot; {{ $order->customer_name ?: 'Tanpa nama' }}
                        </p>
                    </div>

                    <div>
                        <span class="pc-badge {{ $order->paymentBadgeClass() }}">{{ $order->paymentLabel() }}</span>
                    </div>

                    <div>
                        <span class="pc-badge border border-amber-200 bg-white text-amber-950">{{ $order->paymentMethodLabel() }}</span>
                    </div>

                    <p class="font-extrabold tabular-nums text-stone-950 lg:text-right">{{ $format($order->total) }}</p>
                </article>
            @empty
                <p class="p-4 text-sm font-semibold text-stone-500">Belum ada transaksi pada rentang tanggal ini.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
