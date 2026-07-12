@extends('layouts.app')

@section('title', 'Dapur')
@section('auto_refresh', '12')
@section('order_signal', $orders->map(fn ($order) => $order->id.':'.$order->status.':'.optional($order->updated_at)->timestamp)->implode('|'))

@section('content')
<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Kitchen display</p>
            <h1 class="pc-title">Antrian Dapur</h1>
        </div>
        <form method="GET" action="{{ route('kitchen.orders') }}">
            <button class="pc-button-secondary">
                Refresh
            </button>
        </form>
    </div>

    <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $order)
            <article @class([
                'pc-card p-4 transition duration-200',
                'pc-order-card-accepted' => $order->status === 'accepted',
                'pc-order-card-preparing' => $order->status === 'preparing',
                'pc-order-card-ready' => $order->status === 'ready',
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold text-stone-950">{{ $order->table->name }}</h2>
                        <p class="pc-subtle mt-1">{{ $order->code }} &middot; {{ $order->created_at->format('H:i') }}</p>
                    </div>
                    <span @class([
                        'pc-badge',
                        'pc-status-accepted' => $order->status === 'accepted',
                        'pc-status-preparing' => $order->status === 'preparing',
                        'pc-status-ready' => $order->status === 'ready',
                    ])>{{ $order->statusLabel() }}</span>
                </div>

                <div class="mt-4 divide-y divide-amber-100 rounded-lg border border-amber-100 bg-white/95">
                    @foreach ($order->items as $item)
                        <div class="flex items-start gap-3 p-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-stone-950 text-base font-extrabold text-amber-50">{{ $item->quantity }}</span>
                            <div>
                                <p class="font-bold text-stone-950">{{ $item->name_snapshot }}</p>
                                @if ($item->variant)
                                    <p class="mt-1 text-sm font-extrabold text-amber-900">{{ \App\Models\MenuItem::variantLabel($item->variant) }}</p>
                                @endif
                                @if ($item->notes)
                                    <p class="mt-1 text-sm font-semibold text-amber-800">{{ $item->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($order->notes)
                    <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">{{ $order->notes }}</p>
                @endif

                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                    <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="preparing">
                        <button @disabled($order->status === 'preparing') class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm font-bold text-indigo-900 transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                            Proses
                        </button>
                    </form>
                    <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="ready">
                        <button @disabled($order->status === 'ready') class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">
                            Siap
                        </button>
                    </form>
                    <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button class="pc-button-primary w-full">
                            Selesai
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <p class="pc-card p-4 text-sm text-stone-500">Tidak ada antrian aktif.</p>
        @endforelse
    </section>
</div>
@endsection
