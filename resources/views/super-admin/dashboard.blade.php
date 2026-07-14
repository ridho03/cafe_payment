@extends('layouts.app')

@section('title', 'Super Admin')
@section('auto_refresh', '15')

@section('content')
@php
    $statusLabels = \App\Models\Cafe::STATUSES;
@endphp

<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Developer / Super Admin</p>
            <h1 class="pc-title">Dashboard Super Admin</h1>
            <p class="pc-subtle mt-2">Pantau cafe, akun, transaksi, Midtrans, dan kondisi server dari satu panel.</p>
        </div>
        <a href="{{ route('super-admin.cafes') }}" class="pc-button-primary">Kelola Cafe</a>
    </div>

    <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8" aria-label="Ringkasan Super Admin">
        <div class="pc-stat">
            <p class="pc-subtle">Cafe</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $stats['cafes'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Admin Cafe</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $stats['admins'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Kasir</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $stats['cashiers'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Dapur</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-stone-950">{{ $stats['kitchens'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Transaksi</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-emerald-700">{{ $stats['transactions'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Midtrans aktif</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-800">{{ $stats['midtrans_integrated'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Mau expired</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-amber-800">{{ $stats['expiring'] }}</p>
        </div>
        <div class="pc-stat">
            <p class="pc-subtle">Expired</p>
            <p class="mt-2 text-2xl font-extrabold tabular-nums text-red-700">{{ $stats['expired'] }}</p>
        </div>
    </section>

    @if ($expiringCafes->isNotEmpty())
        <section class="pc-card mt-5 overflow-hidden border-amber-300">
            <div class="pc-table-head flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <h2 class="font-bold text-stone-950">Perlu perhatian masa aktif</h2>
                    <p class="pc-subtle mt-1">Cafe yang berakhir dalam 7 hari atau sudah melewati tanggal aktif.</p>
                </div>
                <a href="{{ route('super-admin.cafes') }}" class="pc-button-secondary min-h-10 px-3 py-1">Atur Masa Aktif</a>
            </div>
            <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($expiringCafes as $cafe)
                    <article class="rounded-lg border border-amber-100 bg-white/85 p-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="pc-wrap font-bold text-stone-950">{{ $cafe->name }}</p>
                                <p class="pc-subtle mt-1">{{ $cafe->active_until?->format('d M Y') ?? 'Tanpa tanggal' }}</p>
                            </div>
                            <span class="pc-badge {{ $cafe->expiryBadgeClass() }}">{{ $cafe->expiryLabel() }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-5 xl:grid-cols-[1fr_360px]">
        <section class="pc-card overflow-hidden">
            <div class="pc-table-head flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <h2 class="font-bold text-stone-950">Status Cafe</h2>
                <a href="{{ route('super-admin.midtrans') }}" class="text-sm font-bold text-amber-800 hover:text-amber-950">Atur Midtrans</a>
            </div>
            <div class="divide-y divide-amber-100">
                @forelse ($cafes as $cafe)
                    <article class="flex flex-wrap items-center justify-between gap-4 p-4">
                        <div class="min-w-0">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <p class="pc-wrap min-w-0 font-bold text-stone-950">{{ $cafe->name }}</p>
                                <span class="pc-badge {{ $cafe->statusBadgeClass() }}">{{ $cafe->statusLabel() }}</span>
                            </div>
                            <p class="pc-subtle mt-1">
                                {{ $cafe->domain ?: ($cafe->subdomain ?: 'Domain belum diatur') }}
                                &middot; {{ $cafe->users_count }} akun
                            </p>
                        </div>
                        <div class="min-w-0 flex flex-wrap items-center gap-2">
                            @if ($cafe->midtransSetting?->is_integrated)
                                <span class="pc-badge pc-payment-paid">Midtrans {{ ucfirst($cafe->midtransSetting->mode) }}</span>
                            @else
                                <span class="pc-badge pc-payment-unpaid">Midtrans belum aktif</span>
                            @endif
                            <span class="pc-badge {{ $cafe->expiryBadgeClass() }}">
                                {{ $cafe->expiryLabel() }}
                            </span>
                        </div>
                    </article>
                @empty
                    <p class="p-4 text-sm text-stone-500">Belum ada cafe terdaftar.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-5">
            <section class="pc-card p-4">
                <h2 class="font-bold text-stone-950">Ringkasan status</h2>
                <div class="mt-4 grid gap-2">
                    @foreach ($statusLabels as $status => $label)
                        <div class="flex items-center justify-between rounded-lg border border-amber-100 bg-white/80 px-3 py-2">
                            <span class="text-sm font-bold text-stone-700">{{ $label }}</span>
                            <span class="font-extrabold tabular-nums text-stone-950">{{ (int) ($cafeStatuses[$status] ?? 0) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="pc-card overflow-hidden">
                <div class="pc-table-head px-4 py-3">
                    <h2 class="font-bold text-stone-950">Versi & server</h2>
                </div>
                <dl class="divide-y divide-amber-100">
                    @foreach ($system as $label => $value)
                        <div class="grid gap-1 p-4">
                            <dt class="text-xs font-extrabold uppercase text-stone-500">{{ $label }}</dt>
                            <dd class="break-all text-sm font-bold text-stone-950">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </aside>
    </div>
</div>
@endsection
