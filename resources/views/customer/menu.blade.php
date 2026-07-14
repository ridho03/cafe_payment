@extends('layouts.app')

@section('title', 'Pesan Menu - ' . $table->name)
@section('auto_refresh', '20')

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
    $cafeName = $table->cafe?->name ?: $panelBrandName;
    $midtransSetting = $table->cafe?->midtransSetting;
    $midtransReady = $midtransSetting?->is_integrated && filled($midtransSetting?->client_key) && filled($midtransSetting?->server_key);
@endphp

<div class="min-h-dvh">
    <form id="customer-order-form" method="POST" action="{{ route('customer.orders.store', ['table' => $table->code]) }}" class="mx-auto grid max-w-6xl gap-5 px-3 py-4 pb-40 sm:px-4 sm:py-5 lg:grid-cols-[1fr_360px] lg:px-8 lg:pb-8">
        @csrf

        <section class="space-y-5">
            <div class="relative overflow-hidden rounded-lg border border-amber-100 bg-stone-950 p-4 text-amber-50 shadow-[0_24px_70px_rgba(69,36,14,0.18)] sm:p-6">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(217,119,6,0.34),transparent_16rem),linear-gradient(135deg,rgba(38,19,7,0.95),rgba(88,44,14,0.86))]"></div>
                <div class="relative">
                    <p class="text-sm font-extrabold text-amber-200">Scan QR berhasil</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <span class="pc-brand-mark size-14">
                            <img src="{{ $appLogoUrl }}" alt="Logo {{ $cafeName }}" class="pc-brand-logo">
                        </span>
                        <div class="min-w-0">
                            <h1 class="pc-wrap font-display text-3xl leading-tight sm:text-4xl">Menu {{ $cafeName }}</h1>
                            <p class="mt-1 text-sm font-semibold text-amber-100/75">{{ $table->name }} &middot; Kapasitas {{ $table->capacity }} orang</p>
                        </div>
                    </div>
                    <nav class="mt-5 flex gap-2 overflow-x-auto pb-1" aria-label="Kategori menu">
                        @foreach ($categories as $category)
                            <a href="#category-{{ $category->id }}" class="inline-flex min-h-11 shrink-0 items-center rounded-lg bg-white/10 px-3 py-2 text-sm font-extrabold text-amber-50 transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-amber-200">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>

            <div class="pc-soft-panel p-3 sm:p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-extrabold text-stone-950">Pesan dari meja kamu</p>
                        <p class="pc-subtle mt-1">Pilih varian, atur jumlah, lalu checkout saat pesanan sudah pas.</p>
                    </div>
                    <span class="pc-badge pc-status-ready">Siap dipesan</span>
                </div>
            </div>

            @foreach ($categories as $category)
                <section aria-labelledby="category-{{ $category->id }}" class="space-y-3">
                    <h2 id="category-{{ $category->id }}" class="text-lg font-extrabold text-stone-950">{{ $category->name }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($category->items as $item)
                            <article class="pc-card grid grid-cols-[84px_minmax(0,1fr)] gap-3 p-3 transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_24px_60px_rgba(69,36,14,0.12)] min-[390px]:grid-cols-[96px_minmax(0,1fr)] sm:grid-cols-[104px_1fr]">
                                <div data-menu-image class="relative aspect-square overflow-hidden rounded-lg bg-amber-100 shadow-inner shadow-amber-900/10">
                                    @if ($item->imageSrc())
                                        <img src="{{ $item->imageSrc() }}" alt="{{ $item->name }}" class="h-full w-full object-cover" loading="lazy" onerror="this.classList.add('hidden'); this.closest('[data-menu-image]').querySelector('[data-image-fallback]').classList.remove('hidden');">
                                    @else
                                    @endif
                                    <div data-image-fallback @class([
                                        'grid h-full place-items-center bg-gradient-to-br from-stone-950 to-amber-900 text-center text-xs font-extrabold text-amber-50',
                                        'hidden' => $item->imageSrc(),
                                    ])>
                                        {{ \Illuminate\Support\Str::of($item->name)->substr(0, 2)->upper() }}
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold leading-5 text-stone-950">{{ $item->name }}</h3>
                                    <p class="mt-1 line-clamp-2 text-sm leading-5 text-stone-600">{{ $item->description }}</p>
                                    @if ($item->hasVariants())
                                        <div class="mt-3 space-y-3">
                                            @foreach ($item->availableVariantGroups() as $group)
                                                <fieldset>
                                                    <legend class="mb-1 text-xs font-extrabold uppercase text-stone-500">{{ $group['name'] }}</legend>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        @foreach ($group['options'] as $option)
                                                            <label class="relative">
                                                                <input
                                                                    type="radio"
                                                                    name="item_variants[{{ $item->id }}][{{ $loop->parent->index }}]"
                                                                    value="{{ $option['value'] }}"
                                                                    data-variant-input="{{ $item->id }}"
                                                                    data-variant-group="{{ $group['name'] }}"
                                                                    data-variant-label="{{ $option['label'] }}"
                                                                    data-variant-price-delta="{{ $option['price_delta'] }}"
                                                                    @checked($loop->first)
                                                                    class="peer sr-only"
                                                                >
                                                                <span class="flex min-h-11 items-center justify-center rounded-lg border border-amber-100 bg-white px-2 text-center text-sm font-extrabold text-stone-600 transition peer-checked:border-stone-950 peer-checked:bg-stone-950 peer-checked:text-amber-50 sm:px-3">
                                                                    {{ $option['label'] }}@if($option['price_delta'] > 0) +{{ $format($option['price_delta']) }}@endif
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                        <p class="pc-price">{{ $format($item->price) }}</p>
                                        <div class="flex items-center gap-2 rounded-lg border border-amber-100 bg-amber-50/70 p-1" aria-label="Jumlah {{ $item->name }}">
                                            <button
                                                type="button"
                                                data-qty-minus="{{ $item->id }}"
                                                class="grid size-10 place-items-center rounded-lg bg-white text-lg font-extrabold text-stone-700 shadow-sm transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-700 disabled:cursor-not-allowed disabled:opacity-40"
                                                aria-label="Kurangi {{ $item->name }}"
                                            >-</button>
                                            <input
                                                name="items[{{ $item->id }}]"
                                                type="number"
                                                inputmode="numeric"
                                                min="0"
                                                max="20"
                                                value="{{ old('items.' . $item->id, 0) }}"
                                                data-cart-input
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-price="{{ $item->price }}"
                                                class="h-10 w-12 rounded-lg border border-transparent bg-transparent px-1 text-center text-base font-extrabold tabular-nums text-stone-950 focus:border-amber-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-700/20"
                                            >
                                            <button
                                                type="button"
                                                data-qty-plus="{{ $item->id }}"
                                                class="grid size-10 place-items-center rounded-lg bg-stone-950 text-lg font-extrabold text-amber-50 shadow-sm transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-amber-700"
                                                aria-label="Tambah {{ $item->name }}"
                                            >+</button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </section>

        <aside class="fixed inset-x-0 bottom-0 z-20 max-h-[82dvh] overflow-y-auto rounded-t-lg border border-amber-100 bg-white/95 p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-2xl shadow-amber-900/15 backdrop-blur-xl sm:p-4 lg:sticky lg:top-24 lg:h-fit lg:max-h-none lg:rounded-lg lg:border lg:pb-4 lg:shadow-[0_20px_60px_rgba(69,36,14,0.12)]">
            <div class="mx-auto max-w-6xl lg:mx-0">
                <div class="mx-auto mb-2 h-1 w-12 rounded-full bg-amber-200 lg:hidden" aria-hidden="true"></div>
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-display text-xl leading-tight text-stone-950 lg:text-2xl">Checkout</h2>
                        <p class="mt-0.5 text-sm font-extrabold tabular-nums text-amber-900 lg:hidden" id="cart-mobile-total">Rp 0</p>
                    </div>
                    <span id="cart-count" class="pc-badge bg-amber-100 text-amber-900">0 item</span>
                </div>

                <details data-checkout-details class="mt-2 rounded-lg border border-amber-100 bg-white/80 p-3 lg:mt-3 lg:border-0 lg:bg-transparent lg:p-0">
                    <summary class="cursor-pointer text-sm font-extrabold text-stone-800 lg:hidden">Lihat rincian checkout</summary>
                    <div class="mt-3 lg:mt-0">
                        <div class="pc-soft-panel p-3" aria-live="polite">
                            <div id="cart-empty" class="text-sm font-semibold text-stone-600">
                                Pilih menu untuk melihat total pesanan.
                            </div>
                            <div id="cart-items" class="hidden space-y-2"></div>
                            <dl class="mt-3 space-y-2 border-t border-amber-100 pt-3 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-stone-600">Subtotal</dt>
                                    <dd id="cart-subtotal" class="font-bold tabular-nums text-stone-950">Rp 0</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-stone-600">Biaya layanan 5%</dt>
                                    <dd id="cart-service" class="font-bold tabular-nums text-stone-950">Rp 0</dd>
                                </div>
                                <div class="flex justify-between gap-3 border-t border-amber-100 pt-2 text-base">
                                    <dt class="font-extrabold text-stone-950">Total</dt>
                                    <dd id="cart-total" class="font-extrabold tabular-nums text-amber-800">Rp 0</dd>
                                </div>
                            </dl>
                        </div>

                        <details class="mt-3 rounded-lg border border-amber-100 bg-white/80 p-3">
                            <summary class="cursor-pointer text-sm font-extrabold text-stone-800">Detail pelanggan opsional</summary>
                            <div class="mt-3 grid gap-3">
                                <label class="pc-label">
                                    Nama
                                    <input name="customer_name" value="{{ old('customer_name') }}" class="pc-input" placeholder="Opsional">
                                </label>
                                <label class="pc-label">
                                    Nomor WhatsApp
                                    <input name="customer_phone" value="{{ old('customer_phone') }}" inputmode="tel" class="pc-input" placeholder="Opsional">
                                </label>
                                <label class="pc-label">
                                    Catatan pesanan
                                    <textarea name="notes" rows="2" class="pc-input py-2" placeholder="Contoh: gula sedikit">{{ old('notes') }}</textarea>
                                </label>
                            </div>
                        </details>
                        <fieldset class="mt-3 rounded-lg border border-amber-100 bg-white/80 p-3">
                            <legend class="text-sm font-extrabold text-stone-800">Metode pembayaran</legend>
                            <div class="mt-3 grid gap-2">
                                <label class="flex min-h-12 items-center gap-3 rounded-lg border border-amber-100 bg-amber-50/70 px-3 text-sm font-bold text-stone-700">
                                    <input name="payment_method" type="radio" value="cash" checked class="size-4 border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800">
                                    <span>
                                        Cash
                                        <span class="block text-xs font-semibold text-stone-500">Bayar di kasir.</span>
                                    </span>
                                </label>
                                <label @class([
                                    'flex min-h-12 items-center gap-3 rounded-lg border px-3 text-sm font-bold',
                                    'border-amber-100 bg-amber-50/70 text-stone-700' => $midtransReady,
                                    'border-stone-200 bg-stone-50 text-stone-400' => ! $midtransReady,
                                ])>
                                    <input name="payment_method" type="radio" value="midtrans_snap" @disabled(! $midtransReady) class="size-4 border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800 disabled:opacity-50">
                                    <span>
                                        Cashless
                                        <span class="block text-xs font-semibold {{ $midtransReady ? 'text-stone-500' : 'text-stone-400' }}">
                                            {{ $midtransReady ? 'QRIS, e-wallet, kartu, atau VA.' : 'Belum aktif untuk cafe ini.' }}
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>
                    </div>
                </details>
                <button data-submit-order class="pc-button-primary mt-3 min-h-12 w-full text-base lg:mt-4">
                    Pilih menu dulu
                </button>
            </div>
        </aside>
    </form>
</div>

<script>
    (function () {
        const form = document.getElementById('customer-order-form');
        if (!form) return;

        const inputs = Array.from(form.querySelectorAll('[data-cart-input]'));
        const minusButtons = Array.from(form.querySelectorAll('[data-qty-minus]'));
        const plusButtons = Array.from(form.querySelectorAll('[data-qty-plus]'));
        const count = document.getElementById('cart-count');
        const empty = document.getElementById('cart-empty');
        const list = document.getElementById('cart-items');
        const subtotalEl = document.getElementById('cart-subtotal');
        const serviceEl = document.getElementById('cart-service');
        const totalEl = document.getElementById('cart-total');
        const mobileTotalEl = document.getElementById('cart-mobile-total');
        const submit = form.querySelector('[data-submit-order]');
        const checkoutDetails = form.querySelector('[data-checkout-details]');
        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        });

        function money(amount) {
            return formatter.format(amount).replace(/\s/g, ' ');
        }

        function clampQuantity(input) {
            const min = Number(input.min || 0);
            const max = Number(input.max || 20);
            const value = Math.max(min, Math.min(max, Number(input.value || 0)));
            input.value = value || 0;
            return value;
        }

        function render() {
            const selected = inputs
                .map((input) => {
                    const variantInputs = Array.from(form.querySelectorAll('[data-variant-input="' + input.dataset.id + '"]:checked'));
                    const variantDelta = variantInputs.reduce((sum, variantInput) => sum + Number(variantInput.dataset.variantPriceDelta || 0), 0);

                    return {
                        name: input.dataset.name,
                        variant: variantInputs.map((variantInput) => variantInput.dataset.variantGroup + ': ' + variantInput.dataset.variantLabel).join(', '),
                        price: Number(input.dataset.price || 0) + variantDelta,
                        quantity: clampQuantity(input)
                    };
                })
                .filter((item) => item.quantity > 0);

            const itemCount = selected.reduce((sum, item) => sum + item.quantity, 0);
            const subtotal = selected.reduce((sum, item) => sum + item.price * item.quantity, 0);
            const service = Math.round(subtotal * 0.05);
            const total = subtotal + service;

            count.textContent = itemCount + ' item';
            subtotalEl.textContent = money(subtotal);
            serviceEl.textContent = money(service);
            totalEl.textContent = money(total);
            if (mobileTotalEl) mobileTotalEl.textContent = money(total);
            submit.textContent = itemCount > 0 ? 'Buat Pesanan' : 'Pilih menu dulu';
            submit.disabled = itemCount === 0;

            list.replaceChildren();
            empty.classList.toggle('hidden', itemCount > 0);
            list.classList.toggle('hidden', itemCount === 0);

            selected.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'flex items-start justify-between gap-3 text-sm';

                const name = document.createElement('div');
                name.className = 'font-semibold text-stone-700';
                name.textContent = item.quantity + 'x ' + item.name + (item.variant ? ' - ' + item.variant : '');

                const price = document.createElement('div');
                price.className = 'shrink-0 font-bold tabular-nums text-stone-950';
                price.textContent = money(item.price * item.quantity);

                row.append(name, price);
                list.append(row);
            });

            inputs.forEach((input) => {
                const minus = form.querySelector('[data-qty-minus="' + input.dataset.id + '"]');
                if (minus) {
                    minus.disabled = Number(input.value || 0) <= Number(input.min || 0);
                }
            });
        }

        inputs.forEach((input) => input.addEventListener('input', render));
        minusButtons.forEach((button) => button.addEventListener('click', function () {
            const input = form.querySelector('[data-cart-input][data-id="' + button.dataset.qtyMinus + '"]');
            if (!input) return;

            input.value = Math.max(Number(input.min || 0), Number(input.value || 0) - 1);
            render();
        }));
        plusButtons.forEach((button) => button.addEventListener('click', function () {
            const input = form.querySelector('[data-cart-input][data-id="' + button.dataset.qtyPlus + '"]');
            if (!input) return;

            input.value = Math.min(Number(input.max || 20), Number(input.value || 0) + 1);
            render();
        }));
        form.querySelectorAll('[data-variant-input]').forEach((input) => input.addEventListener('change', render));
        function syncCheckoutDetails() {
            if (!checkoutDetails) return;

            checkoutDetails.open = window.matchMedia('(min-width: 1024px)').matches;
        }

        window.addEventListener('resize', syncCheckoutDetails);
        syncCheckoutDetails();
        render();
    })();
</script>
@endsection
