@extends('layouts.app')

@section('title', config('app.name', 'Payment Cafe'))

@section('content')
<div class="min-h-dvh px-4 py-8 sm:px-6 lg:px-8">
    <section class="mx-auto grid max-w-6xl overflow-hidden rounded-lg border border-amber-100 bg-white/90 shadow-[0_30px_80px_rgba(69,36,14,0.13)] backdrop-blur lg:grid-cols-[1.1fr_0.9fr]">
        <div class="p-6 sm:p-10">
            <div class="flex items-center gap-3">
                <span class="pc-brand-mark">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 10h16M6 10v9h12v-9M8 6h8l2 4H6l2-4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div>
                    <p class="pc-kicker">Payment Cafe</p>
                    <h1 class="font-display text-4xl leading-tight text-stone-950 sm:text-5xl">POS hangat untuk coffee shop modern.</h1>
                </div>
            </div>

            <p class="mt-5 max-w-2xl text-base leading-7 text-stone-600">
                Kelola order QR, pembayaran kasir, dapur, menu, dan meja dari satu alur yang nyaman dipakai saat cafe sedang ramai.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('home') }}" class="pc-button-primary">Buka Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="pc-button-primary">Masuk ke POS</a>
                @endauth
            </div>
        </div>

        <div class="relative min-h-[360px] bg-stone-950 p-6 text-amber-50">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(217,119,6,0.35),transparent_18rem),linear-gradient(135deg,rgba(38,19,7,0.95),rgba(89,113,90,0.72))]"></div>
            <div class="relative grid h-full content-end gap-3">
                <div class="rounded-lg border border-amber-100/20 bg-white/10 p-4 backdrop-blur">
                    <p class="text-sm font-extrabold text-amber-200">Alur operasional</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="font-extrabold">Meja</p>
                            <p class="mt-1 text-sm text-amber-100/75">Scan QR</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="font-extrabold">Kasir</p>
                            <p class="mt-1 text-sm text-amber-100/75">Konfirmasi bayar</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="font-extrabold">Dapur</p>
                            <p class="mt-1 text-sm text-amber-100/75">Siapkan order</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
