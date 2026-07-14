@extends('layouts.app')

@section('title', 'Midtrans Cafe')

@section('content')
<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Super Admin</p>
            <h1 class="pc-title">Pengaturan Midtrans per Cafe</h1>
            <p class="pc-subtle mt-2">Simpan Merchant ID, Client Key, dan Server Key per cafe. Key disimpan terenkripsi dan hanya ditampilkan tersamarkan.</p>
        </div>
        <a href="{{ route('super-admin.dashboard') }}" class="pc-button-secondary">Dashboard</a>
    </div>

    <section class="mt-6 grid gap-4">
        @forelse ($cafes as $cafe)
            @php($setting = $cafe->midtransSetting)
            <article class="pc-card overflow-hidden">
                <div class="pc-table-head flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <h2 class="pc-wrap font-bold text-stone-950">{{ $cafe->name }}</h2>
                        <p class="pc-subtle">{{ $cafe->domain ?: ($cafe->subdomain ?: 'Domain belum diatur') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="pc-badge {{ $setting?->is_integrated ? 'pc-payment-paid' : 'pc-payment-unpaid' }}">
                            {{ $setting?->is_integrated ? 'Terintegrasi' : 'Belum aktif' }}
                        </span>
                        <span class="pc-badge pc-payment-refunded">{{ ucfirst($setting?->mode ?? 'sandbox') }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('super-admin.midtrans.update', $cafe) }}" class="grid gap-4 p-4 lg:grid-cols-4">
                    @csrf
                    @method('PATCH')
                    <label class="pc-label">
                        Mode
                        <select name="mode" required class="pc-input">
                            <option value="sandbox" @selected(($setting?->mode ?? 'sandbox') === 'sandbox')>Sandbox</option>
                            <option value="production" @selected($setting?->mode === 'production')>Production</option>
                        </select>
                    </label>
                    <label class="pc-label">
                        Merchant ID
                        <input name="merchant_id" value="{{ $setting?->merchant_id }}" class="pc-input" placeholder="G123456789">
                    </label>
                    <label class="pc-label">
                        Client Key baru
                        <input name="client_key" type="password" autocomplete="new-password" class="pc-input" placeholder="{{ $setting ? $setting->maskedClientKey() : 'Belum diisi' }}">
                    </label>
                    <label class="pc-label">
                        Server Key baru
                        <input name="server_key" type="password" autocomplete="new-password" class="pc-input" placeholder="{{ $setting ? $setting->maskedServerKey() : 'Belum diisi' }}">
                    </label>

                    <div class="rounded-lg border border-amber-100 bg-white/75 p-3 lg:col-span-2">
                        <p class="text-xs font-extrabold uppercase text-stone-500">Key tersimpan</p>
                        <div class="mt-2 grid gap-2 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-semibold text-stone-600">Client Key</span>
                                <span class="pc-wrap min-w-0 font-bold text-stone-950">{{ $setting?->maskedClientKey() ?? 'Belum diisi' }}</span>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-semibold text-stone-600">Server Key</span>
                                <span class="pc-wrap min-w-0 font-bold text-stone-950">{{ $setting?->maskedServerKey() ?? 'Belum diisi' }}</span>
                            </div>
                        </div>
                    </div>

                    <label class="pc-label flex min-h-11 items-center gap-2 self-end rounded-lg border border-amber-100 bg-white/80 px-3 py-2">
                        <input name="is_integrated" value="1" type="checkbox" @checked($setting?->is_integrated) class="size-4 accent-amber-800">
                        Status integrasi aktif
                    </label>
                    <label class="pc-label flex min-h-11 items-center gap-2 self-end rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-red-800">
                        <input name="clear_keys" value="1" type="checkbox" class="size-4 accent-red-700">
                        Kosongkan key
                    </label>
                    <div class="flex items-end lg:col-span-4">
                        <button class="pc-button-primary ml-auto min-h-12 w-full sm:w-auto">Simpan Midtrans</button>
                    </div>
                </form>
            </article>
        @empty
            <div class="pc-card p-4 text-sm font-semibold text-stone-600">Belum ada cafe untuk dikonfigurasi.</div>
        @endforelse
    </section>
</div>
@endsection
