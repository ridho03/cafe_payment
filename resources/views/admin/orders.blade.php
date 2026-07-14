@extends('layouts.app')

@section('title', 'Pesanan')
@section('auto_refresh', '5')
@section('order_signal', $orders->getCollection()->map(fn ($order) => $order->id.':'.$order->status.':'.$order->payment_status.':'.optional($order->updated_at)->timestamp)->implode('|'))

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
@endphp

<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Dapur dan kasir</p>
            <h1 class="pc-title">Pesanan</h1>
        </div>
    </div>

    <section class="pc-card mt-6 overflow-hidden">
        <div class="divide-y divide-amber-100">
            @forelse ($orders as $order)
                <article class="p-4">
                    <div class="grid gap-4 lg:grid-cols-[1fr_180px_180px] lg:items-start">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-stone-950">{{ $order->code }}</h2>
                                <span class="pc-badge border border-amber-200 bg-amber-50 text-amber-950">{{ $order->table->name }}</span>
                                <span class="pc-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                                <span class="pc-badge {{ $order->paymentBadgeClass() }}">{{ $order->paymentLabel() }}</span>
                                <span class="pc-badge border border-amber-200 bg-white text-amber-950">{{ $order->paymentMethodLabel() }}</span>
                            </div>
                            <p class="pc-subtle mt-1">{{ $order->created_at->format('d M Y H:i') }} &middot; {{ $order->customer_name ?: 'Tanpa nama' }}</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($order->items as $item)
                                    <div class="pc-list-chip">
                                        <span class="font-bold">{{ $item->quantity }}x</span> {{ $item->name_snapshot }}
                                        @if ($item->variant)
                                            <span class="font-bold text-amber-900">- {{ \App\Models\MenuItem::variantLabel($item->variant) }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @if ($order->notes)
                                <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">{{ $order->notes }}</p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="space-y-2">
                            @csrf
                            @method('PATCH')
                            <label class="pc-label">
                                Status order
                                <select name="status" class="pc-input">
                                    @foreach (\App\Models\Order::STATUS_FLOW as $value => $label)
                                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="pc-button-secondary w-full">
                                Update
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.orders.payment', $order) }}" class="space-y-2">
                            @csrf
                            @method('PATCH')
                            <label class="pc-label">
                                Pembayaran
                                <select name="payment_status" class="pc-input">
                                    @foreach (\App\Models\Order::PAYMENT_FLOW as $value => $label)
                                        <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <p class="text-right pc-price">{{ $format($order->total) }}</p>
                            <button class="pc-button-primary w-full">
                                Simpan bayar
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="p-4 text-sm text-stone-500">Belum ada pesanan.</p>
            @endforelse
        </div>
    </section>

    <div class="mt-5">
        {{ $orders->links() }}
    </div>
</div>
@endsection
