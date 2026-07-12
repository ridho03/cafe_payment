@extends('layouts.app')

@section('title', 'Login Admin')

@section('content')
<div class="grid min-h-dvh place-items-center px-4 py-4 lg:h-dvh lg:min-h-0 lg:overflow-hidden lg:px-6">
    <section class="grid w-full max-w-6xl overflow-hidden rounded-lg border border-amber-100 bg-white/92 shadow-[0_30px_80px_rgba(69,36,14,0.14)] backdrop-blur lg:h-[calc(100dvh-3rem)] lg:max-h-[760px] lg:min-h-[560px] lg:grid-cols-[minmax(0,1.05fr)_420px]">
        <div class="relative hidden min-h-0 overflow-hidden bg-stone-950 text-amber-50 lg:block">
            <img src="{{ asset('images/login-coffee.jpg') }}" alt="Coffee shop counter" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(28,13,5,0.92),rgba(69,36,14,0.58)_45%,rgba(4,47,46,0.72))]"></div>
            <div class="absolute left-8 top-8 inline-flex rounded-full border border-amber-200/20 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-normal text-amber-100 backdrop-blur">
                Cafe operations panel
            </div>
            <div class="absolute inset-x-8 bottom-8 max-h-[calc(100%-6rem)]">
                <div class="max-w-xl">
                    <p class="text-sm font-extrabold text-amber-200">Portal internal</p>
                    <h2 class="mt-2 font-display text-4xl leading-tight xl:text-5xl">Kelola order, kasir, dan dapur dari satu layar.</h2>
                    <p class="mt-4 max-w-md text-sm font-semibold leading-6 text-amber-50/80">
                        Dibuat untuk alur coffee shop yang cepat: pelanggan scan QR, kasir konfirmasi pembayaran, dapur menerima antrian.
                    </p>
                </div>
                <div class="mt-6 grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-lg border border-white/10 bg-white/12 p-3 backdrop-blur">
                        <p class="font-extrabold">QR Menu</p>
                        <p class="mt-1 text-amber-100/75">Order meja</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/12 p-3 backdrop-blur">
                        <p class="font-extrabold">Kasir</p>
                        <p class="mt-1 text-amber-100/75">Struk thermal</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/12 p-3 backdrop-blur">
                        <p class="font-extrabold">Dapur</p>
                        <p class="mt-1 text-amber-100/75">Auto refresh</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex min-h-0 flex-col justify-center p-5 sm:p-8 lg:p-8 xl:p-10">
            <div class="flex items-center gap-3">
                <span class="pc-brand-mark size-12">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 10h16M6 10v9h12v-9M8 6h8l2 4H6l2-4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div>
                    <p class="pc-kicker">{{ config('app.name') }}</p>
                    <h1 class="font-display text-3xl leading-tight text-stone-950">Masuk ke Panel</h1>
                </div>
            </div>
            <p class="pc-subtle mt-4">
                Gunakan akun yang diberikan owner atau supervisor. Akses kasir, dapur, dan admin akan menyesuaikan peran pengguna.
            </p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-4">
                @csrf
                <label class="pc-label">
                    Email
                    <input
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="pc-input h-12"
                        placeholder="nama@domain.com"
                    >
                </label>

                <label class="pc-label">
                    Password
                    <input
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="pc-input h-12"
                        placeholder="Masukkan password"
                    >
                </label>

                <label class="flex min-h-11 items-center gap-3 text-sm font-semibold text-stone-700">
                    <input name="remember" type="checkbox" value="1" class="size-4 rounded border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800">
                    Ingat sesi login
                </label>

                <button class="pc-button-primary min-h-12 w-full">
                    Masuk
                </button>
            </form>
        </div>
    </section>
</div>
@endsection
