@extends('layouts.app')

@section('title', 'Kasir')
@section('auto_refresh', '5')
@section('order_signal', $orders->map(fn ($order) => $order->id.':'.$order->status.':'.$order->payment_status.':'.optional($order->updated_at)->timestamp)->implode('|'))

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
@endphp

<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Pembayaran dan struk</p>
            <h1 class="pc-title">Kasir</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cashier.reports') }}" class="pc-button-secondary">
                Laporan
            </a>
            <form method="GET" action="{{ route('cashier.orders') }}">
                <button class="pc-button-secondary">
                    Refresh
                </button>
            </form>
        </div>
    </div>

    <section class="mt-6 grid gap-3 sm:grid-cols-3" aria-label="Ringkasan kasir">
        <div class="pc-stat">
            <p class="pc-subtle">Belum bayar</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-700">{{ $stats['unpaid'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Lunas hari ini</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700">{{ $stats['paid_today'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Omzet lunas</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $format($stats['revenue_today']) }}</p>
        </div>
    </section>

    <section class="pc-card mt-6 overflow-hidden">
        <div class="pc-table-head grid grid-cols-[1fr_120px_140px_180px] gap-3 px-4 py-3 max-lg:hidden">
            <span>Order</span>
            <span>Status</span>
            <span>Total</span>
            <span>Aksi</span>
        </div>
        <div class="divide-y divide-amber-100">
            @forelse ($orders as $order)
                <article class="grid gap-4 p-4 lg:grid-cols-[1fr_120px_140px_180px] lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-stone-950">{{ $order->code }}</h2>
                            <span class="pc-badge border border-amber-200 bg-amber-50 text-amber-950">{{ $order->table->name }}</span>
                            <span class="pc-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                        </div>
                        <p class="pc-subtle mt-1">{{ $order->created_at->format('H:i') }} &middot; {{ $order->customer_name ?: 'Tanpa nama' }} &middot; {{ $order->items->sum('quantity') }} item</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($order->items as $item)
                                <span class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-semibold text-stone-600">
                                    {{ $item->quantity }}x {{ $item->name_snapshot }}@if($item->variant) - {{ \App\Models\MenuItem::variantLabel($item->variant) }}@endif
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <span class="pc-badge {{ $order->paymentBadgeClass() }}">{{ $order->paymentLabel() }}</span>
                        <span class="pc-badge border border-amber-200 bg-white text-amber-950 mt-2">{{ $order->paymentMethodLabel() }}</span>
                    </div>

                    <p class="pc-price">{{ $format($order->total) }}</p>

                    <div class="grid gap-2">
                        @if ($order->payment_status !== 'paid')
                            <form method="POST" action="{{ route('cashier.orders.payment', $order) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="payment_status" value="paid">
                                <button class="pc-button-primary w-full">
                                    Tandai Lunas
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('cashier.orders.receipt', $order) }}" target="_blank" class="pc-button-secondary w-full">
                            Cetak Struk
                        </a>
                    </div>
                </article>
            @empty
                <p class="p-4 text-sm text-stone-500">Belum ada pesanan.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
