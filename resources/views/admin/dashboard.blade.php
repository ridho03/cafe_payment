@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('auto_refresh', '8')
@section('order_signal', $orders->map(fn ($order) => $order->id.':'.$order->status.':'.$order->payment_status.':'.optional($order->updated_at)->timestamp)->implode('|'))

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
@endphp

<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Operasional hari ini</p>
            <h1 class="pc-title">Dashboard Kasir</h1>
        </div>
        <a href="{{ route('admin.tables') }}" class="pc-button-primary">
            Kelola QR Meja
        </a>
    </div>

    @if ($currentCafe && ($currentCafe->expiresSoon() || $currentCafe->isPastActiveUntil() || $currentCafe->status === 'expired'))
        <section class="pc-card mt-5 border-amber-300 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-extrabold text-amber-900">Masa aktif cafe</p>
                    <h2 class="pc-wrap mt-1 font-bold text-stone-950">{{ $currentCafe->name }}</h2>
                    <p class="pc-subtle mt-1">Hubungi Super Admin untuk memperpanjang masa aktif cafe.</p>
                </div>
                <span class="pc-badge {{ $currentCafe->expiryBadgeClass() }}">{{ $currentCafe->expiryLabel() }}</span>
            </div>
        </section>
    @endif

    <section class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5" aria-label="Ringkasan">
        <div class="pc-stat">
            <p class="pc-subtle">Order hari ini</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $stats['today_orders'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Revenue lunas</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700">{{ $format($stats['today_revenue']) }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Order aktif</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-800">{{ $stats['open_orders'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Meja aktif</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $stats['active_tables'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Menu tersedia</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $stats['available_items'] }}</p>
        </div>
    </section>

    <div class="mt-6 grid gap-5 lg:grid-cols-[1fr_340px]">
        <section class="pc-card overflow-hidden">
            <div class="pc-table-head flex items-center justify-between gap-3 px-4 py-3">
                <h2 class="font-bold text-stone-950">Order terbaru</h2>
                <a href="{{ route('admin.orders') }}" class="text-sm font-bold text-amber-800 hover:text-amber-950">Lihat semua</a>
            </div>
            <div class="divide-y divide-amber-100">
                @forelse ($orders as $order)
                    <article class="p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-stone-950">{{ $order->code }}</p>
                                <p class="pc-subtle mt-1">{{ $order->table->name }} &middot; {{ $order->items->sum('quantity') }} item</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold tabular-nums text-stone-950">{{ $format($order->total) }}</p>
                                <span class="pc-badge {{ $order->paymentBadgeClass() }} mt-1">{{ $order->paymentLabel() }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="p-4 text-sm text-stone-500">Belum ada order masuk.</p>
                @endforelse
            </div>
        </section>

        <section class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Menu terlaris</h2>
            <div class="mt-4 space-y-3">
                @forelse ($popularItems as $item)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-amber-100 bg-amber-50/80 px-3 py-2">
                        <span class="text-sm font-semibold text-stone-700">{{ $item->name_snapshot }}</span>
                        <span class="font-bold tabular-nums text-emerald-700">{{ $item->sold }}</span>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Data akan muncul setelah ada transaksi.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
