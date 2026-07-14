@extends('layouts.app')

@section('title', 'Status ' . $order->code)
@section('auto_refresh', '4')
@section('order_signal_key', 'customer-status-'.$order->id)
@section('order_signal', $order->id.':'.$order->status.':'.$order->payment_status.':'.optional($order->updated_at)->timestamp)

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
    $midtransSetting = $order->table->cafe?->midtransSetting;
    $midtransClientKey = $midtransSetting?->client_key;
    $midtransReady = $midtransSetting?->is_integrated && filled($midtransClientKey) && filled($midtransSetting?->server_key);
    $snapUrl = $midtransSetting?->mode === 'production'
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
    $isPaid = $order->payment_status === 'paid';
    $isFailed = $order->payment_status === 'failed';
    $isRefunded = $order->payment_status === 'refunded';
    $usesMidtrans = $order->payment_method === 'midtrans_snap';
    $steps = [
        [
            'label' => 'Pesanan',
            'description' => 'Order dibuat',
            'done' => true,
            'active' => ! $isPaid,
        ],
        [
            'label' => 'Bayar',
            'description' => $isPaid ? 'Pembayaran lunas' : ($usesMidtrans ? 'Bayar cashless' : 'Bayar di kasir'),
            'done' => $isPaid,
            'active' => ! $isPaid && ! $isFailed && ! $isRefunded,
        ],
        [
            'label' => 'Dapur',
            'description' => match ($order->status) {
                'accepted' => 'Diterima dapur',
                'preparing' => 'Sedang diproses',
                'ready' => 'Siap diambil',
                'completed' => 'Selesai',
                default => 'Setelah bayar',
            },
            'done' => in_array($order->status, ['preparing', 'ready', 'completed'], true),
            'active' => $isPaid && in_array($order->status, ['accepted', 'preparing'], true),
        ],
        [
            'label' => 'Selesai',
            'description' => $order->status === 'completed' ? 'Terima kasih' : 'Menunggu selesai',
            'done' => $order->status === 'completed',
            'active' => $isPaid && $order->status === 'ready',
        ],
    ];

    $paymentMessage = match ($order->payment_status) {
        'paid' => 'Pembayaran diterima. Pesanan kamu sudah masuk ke alur dapur.',
        'failed' => 'Pembayaran gagal. Kamu bisa membuat pesanan baru atau minta bantuan kasir.',
        'refunded' => 'Pembayaran sudah direfund.',
        default => $order->payment_method === 'cash'
            ? 'Silakan bayar cash di kasir. Setelah kasir menandai lunas, pesanan masuk ke alur dapur.'
            : ($order->midtrans_snap_token
            ? 'Pembayaran cashless siap dibuka.'
            : 'Pembayaran cashless belum siap. Coba tekan tombol di bawah atau minta bantuan kasir.'),
    };
@endphp

<div class="min-h-dvh px-4 py-6">
    <section class="pc-card mx-auto max-w-2xl overflow-hidden">
        <div class="bg-stone-950 p-5 text-amber-50 sm:p-6">
            <p class="text-sm font-extrabold text-amber-200">Pesanan berhasil dibuat</p>
            <div class="mt-1 flex flex-wrap items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="pc-brand-mark size-14">
                        <img src="{{ $appLogoUrl }}" alt="Logo {{ $order->table->cafe?->name ?: config('app.name') }}" class="pc-brand-logo">
                    </span>
                    <div class="min-w-0">
                        <h1 class="font-display text-3xl leading-tight sm:text-4xl">{{ $order->code }}</h1>
                        <p class="mt-1 text-sm font-semibold text-amber-100/75">{{ $order->table->name }} &middot; {{ $order->created_at->format('H:i') }}</p>
                    </div>
                </div>
                <span class="pc-badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
            </div>
        </div>
        <div class="p-5 sm:p-6">
        <ol class="mt-5 grid gap-2 sm:grid-cols-4" aria-label="Progress pesanan">
            @foreach ($steps as $step)
                <li @class([
                    'rounded-lg border p-3',
                    'border-stone-900 bg-stone-950 text-amber-50' => $step['done'],
                    'border-amber-200 bg-amber-50 text-stone-950' => ! $step['done'] && $step['active'],
                    'border-stone-200 bg-stone-50' => ! $step['done'] && ! $step['active'],
                ])>
                    <div class="flex items-center gap-2">
                        <span @class([
                            'grid size-7 shrink-0 place-items-center rounded-full text-xs font-extrabold',
                            'bg-amber-200 text-stone-950' => $step['done'],
                            'bg-stone-950 text-amber-50' => ! $step['done'] && $step['active'],
                            'bg-stone-200 text-stone-600' => ! $step['done'] && ! $step['active'],
                        ])>{{ $loop->iteration }}</span>
                        <span @class([
                            'font-bold',
                            'text-amber-50' => $step['done'],
                            'text-stone-950' => ! $step['done'],
                        ])>{{ $step['label'] }}</span>
                    </div>
                    <p @class([
                        'mt-2 text-xs font-semibold',
                        'text-amber-100/80' => $step['done'],
                        'text-stone-600' => ! $step['done'],
                    ])>{{ $step['description'] }}</p>
                </li>
            @endforeach
        </ol>

        <div class="mt-5 divide-y divide-amber-100 overflow-hidden rounded-lg border border-amber-100 bg-white">
            @foreach ($order->items as $item)
                <div class="flex items-start justify-between gap-3 p-3">
                    <div>
                        <p class="font-bold text-stone-950">{{ $item->name_snapshot }}</p>
                        @if ($item->variant)
                            <p class="mt-1 text-xs font-extrabold text-amber-900">{{ \App\Models\MenuItem::variantLabel($item->variant) }}</p>
                        @endif
                        <p class="text-sm text-stone-600">{{ $item->quantity }} x {{ $format($item->price_snapshot) }}</p>
                    </div>
                    <p class="font-bold tabular-nums text-stone-950">{{ $format($item->total) }}</p>
                </div>
            @endforeach
        </div>

        <dl class="mt-5 space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Subtotal</dt>
                <dd class="font-bold tabular-nums">{{ $format($order->subtotal) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-stone-600">Biaya layanan</dt>
                <dd class="font-bold tabular-nums">{{ $format($order->service_fee) }}</dd>
            </div>
            <div class="flex justify-between gap-3 border-t border-amber-100 pt-3 text-base">
                <dt class="font-extrabold">Total</dt>
                <dd class="font-extrabold tabular-nums">{{ $format($order->total) }}</dd>
            </div>
        </dl>

        <div class="pc-soft-panel mt-5 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-stone-700">Status bayar</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="pc-badge {{ $order->paymentBadgeClass() }}">{{ $order->paymentLabel() }}</span>
                        <span class="pc-badge border border-amber-200 bg-white text-amber-950">{{ $order->paymentMethodLabel() }}</span>
                    </div>
                </div>
                @if ($order->midtrans_transaction_status)
                    <span class="pc-badge bg-white text-stone-700">
                        Cashless: {{ $order->midtrans_transaction_status }}
                    </span>
                @endif
            </div>
            <p class="mt-3 text-sm font-semibold text-stone-700">{{ $paymentMessage }}</p>
            @if ($order->payment_status !== 'paid')
                <div class="mt-4 space-y-3">
                    @if ($usesMidtrans)
                        @if ($midtransReady && $order->midtrans_snap_token)
                            <button id="pay-with-midtrans" type="button" data-midtrans-pay data-snap-token="{{ $order->midtrans_snap_token }}" data-sync-url="{{ route('orders.midtrans-sync', $order) }}" class="pc-button-primary min-h-12 w-full text-base">
                                Bayar Cashless
                            </button>
                            <form method="POST" action="{{ route('orders.midtrans-sync', $order) }}">
                                @csrf
                                <button class="pc-button-secondary w-full">
                                    Cek Status Pembayaran
                                </button>
                            </form>
                        @elseif ($midtransReady)
                            <form method="POST" action="{{ route('orders.midtrans-token', $order) }}">
                                @csrf
                                <button class="pc-button-primary min-h-12 w-full text-base">
                                    Bayar Cashless
                                </button>
                            </form>
                        @else
                            <div class="rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm font-semibold text-amber-900">
                                Cashless belum aktif untuk cafe ini. Silakan pilih cash atau hubungi kasir.
                            </div>
                        @endif
                    @else
                        <div class="rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm font-semibold text-amber-900">
                            Tunjukkan kode order ini ke kasir untuk pembayaran cash.
                        </div>
                    @endif

                    @if (app()->environment('local', 'testing'))
                        <form method="POST" action="{{ route('orders.simulate-payment', $order) }}">
                            @csrf
                            <button class="pc-button-secondary w-full">
                                Tandai Pembayaran Lunas
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <a href="{{ route('customer.menu', ['table' => $order->table->code]) }}" class="pc-button-secondary">
                Tambah pesanan lagi
            </a>
        </div>
        </div>
    </section>
</div>

@if ($order->payment_status !== 'paid' && $usesMidtrans && $midtransReady && $order->midtrans_snap_token)
    <script src="{{ $snapUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
@endif
@endsection
