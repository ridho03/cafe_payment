@extends('layouts.app')

@section('title', 'Dapur')
@section('auto_refresh', '4')
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

    <section class="mt-6 grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $order)
            <article @class([
                'pc-card overflow-hidden p-0 transition duration-200',
                'pc-order-card-accepted' => $order->status === 'accepted',
                'pc-order-card-preparing' => $order->status === 'preparing',
                'pc-order-card-ready' => $order->status === 'ready',
            ])>
                <div class="border-b border-amber-100 bg-white/70 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="pc-wrap text-xl font-extrabold leading-tight text-stone-950">{{ $order->table->name }}</h2>
                            <p class="mt-1 text-sm font-semibold tabular-nums text-stone-500">{{ $order->code }} &middot; {{ $order->created_at->format('H:i') }}</p>
                        </div>
                        <span @class([
                            'pc-badge shrink-0',
                            'pc-status-accepted' => $order->status === 'accepted',
                            'pc-status-preparing' => $order->status === 'preparing',
                            'pc-status-ready' => $order->status === 'ready',
                        ])>{{ $order->statusLabel() }}</span>
                    </div>
                </div>

                <div class="p-4">
                    <div class="divide-y divide-amber-100 rounded-lg border border-amber-100 bg-white/95">
                        @foreach ($order->items as $item)
                            <div class="grid grid-cols-[44px_minmax(0,1fr)] gap-3 p-3">
                                <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-stone-950 text-lg font-extrabold tabular-nums text-amber-50">{{ $item->quantity }}</span>
                                <div class="min-w-0">
                                    <p class="pc-wrap font-extrabold leading-6 text-stone-950">{{ $item->name_snapshot }}</p>
                                    @if ($item->variant)
                                        <p class="mt-0.5 text-sm font-extrabold text-amber-900">{{ \App\Models\MenuItem::variantLabel($item->variant) }}</p>
                                    @endif
                                    @if ($item->notes)
                                        <p class="mt-1 rounded-lg bg-amber-50 px-2 py-1 text-sm font-semibold text-amber-900">{{ $item->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($order->notes)
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                            <p class="text-xs font-extrabold uppercase text-amber-700">Catatan</p>
                            <p class="pc-wrap mt-1 text-sm font-bold text-amber-950">{{ $order->notes }}</p>
                        </div>
                    @endif

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="preparing">
                            <button @disabled($order->status === 'preparing') class="inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-indigo-200 bg-white px-2 py-2 text-center text-sm font-extrabold leading-tight text-indigo-900 transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                                Proses
                            </button>
                        </form>
                        <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="ready">
                            <button @disabled($order->status === 'ready') class="inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-emerald-200 bg-white px-2 py-2 text-center text-sm font-extrabold leading-tight text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-50">
                                Siap
                            </button>
                        </form>
                        <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-stone-950 px-2 py-2 text-center text-sm font-extrabold leading-tight text-amber-50 shadow-lg shadow-stone-950/15 transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-amber-700">
                                Selesai
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <p class="pc-card p-4 text-sm text-stone-500">Tidak ada antrian aktif.</p>
        @endforelse
    </section>
</div>
@endsection
