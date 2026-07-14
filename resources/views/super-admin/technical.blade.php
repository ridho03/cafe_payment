@extends('layouts.app')

@section('title', 'Teknis Super Admin')
@section('auto_refresh', '20')

@section('content')
<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Super Admin</p>
            <h1 class="pc-title">Fitur Teknis</h1>
            <p class="pc-subtle mt-2">Backup database, clear cache, info sistem, audit log, dan maintenance mode.</p>
        </div>
        <a href="{{ route('super-admin.technical.export-sql') }}" class="pc-button-primary">Export Backup SQL</a>
    </div>

    <section class="mt-6 grid gap-4 lg:grid-cols-3">
        <article class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Backup database</h2>
            <p class="pc-subtle mt-2">Download backup SQL dari database aktif. Simpan file backup di lokasi aman.</p>
            <a href="{{ route('super-admin.technical.export-sql') }}" class="pc-button-primary mt-4 w-full">Download SQL</a>
        </article>

        <article class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Cache aplikasi</h2>
            <p class="pc-subtle mt-2">Bersihkan cache config, route, view, dan cache aplikasi setelah deploy.</p>
            <form method="POST" action="{{ route('super-admin.technical.cache.clear') }}" class="mt-4">
                @csrf
                <button class="pc-button-secondary w-full">Clear Cache</button>
            </form>
        </article>

        <article class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Maintenance mode</h2>
            <p class="pc-subtle mt-2">Status saat ini: <strong class="{{ $isDown ? 'text-red-700' : 'text-emerald-700' }}">{{ $isDown ? 'Aktif' : 'Nonaktif' }}</strong></p>
            <form method="POST" action="{{ route('super-admin.technical.maintenance') }}" class="mt-4">
                @csrf
                <input type="hidden" name="enabled" value="{{ $isDown ? '0' : '1' }}">
                <button class="{{ $isDown ? 'pc-button-secondary' : 'pc-button-primary' }} w-full">
                    {{ $isDown ? 'Matikan Maintenance' : 'Aktifkan Maintenance' }}
                </button>
            </form>
        </article>
    </section>

    <div class="mt-6 grid gap-5 xl:grid-cols-[420px_1fr]">
        <section class="pc-card overflow-hidden">
            <div class="pc-table-head px-4 py-3">
                <h2 class="font-bold text-stone-950">Informasi sistem</h2>
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

        <section class="pc-card overflow-hidden">
            <div class="pc-table-head flex items-center justify-between gap-3 px-4 py-3">
                <h2 class="font-bold text-stone-950">Audit log aktivitas penting</h2>
                <span class="text-xs font-extrabold text-stone-500">40 terbaru</span>
            </div>
            <div class="divide-y divide-amber-100">
                @forelse ($auditLogs as $log)
                    <article class="p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="pc-wrap font-bold text-stone-950">{{ $log->description ?: $log->action }}</p>
                                <p class="pc-subtle mt-1">
                                    {{ $log->user?->name ?? 'Sistem' }}
                                    @if ($log->cafe)
                                        &middot; {{ $log->cafe->name }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="pc-badge pc-payment-refunded">{{ $log->action }}</span>
                                <p class="pc-subtle mt-1">{{ $log->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        @if ($log->metadata)
                            <div class="mt-3 rounded-lg border border-amber-100 bg-amber-50/70 p-3 text-xs font-semibold text-stone-600">
                                @foreach ($log->metadata as $key => $value)
                                    <span class="mr-3">{{ str_replace('_', ' ', $key) }}: {{ is_bool($value) ? ($value ? 'ya' : 'tidak') : $value }}</span>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="p-4 text-sm text-stone-500">Belum ada audit log.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
