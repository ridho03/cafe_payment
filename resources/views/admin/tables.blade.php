@extends('layouts.app')

@section('title', 'Meja QR')
@section('auto_refresh', '60')

@section('content')
<div class="pc-page grid gap-6 lg:grid-cols-[340px_1fr]">
    <section class="pc-card h-fit p-4 lg:sticky lg:top-24">
        <h1 class="font-display text-2xl leading-tight text-stone-950">Tambah Meja</h1>
        <p class="pc-subtle mt-1">Buat QR unik untuk setiap meja agar tamu langsung masuk ke menu.</p>
        <form method="POST" action="{{ route('admin.tables.store') }}" class="mt-4 space-y-3">
            @csrf
            <label class="pc-label">
                Nama meja
                <input name="name" required class="pc-input" placeholder="Meja 01">
            </label>
            <label class="pc-label">
                Kapasitas
                <input name="capacity" required type="number" min="1" max="20" value="2" class="pc-input">
            </label>
            <button class="pc-button-primary min-h-12 w-full">
                Buat Meja
            </button>
        </form>
    </section>

    <section>
        <div class="pc-section-head">
            <div>
                <p class="pc-kicker">Scan untuk pesan</p>
                <h2 class="pc-title">QR Meja</h2>
            </div>
            <a href="{{ route('admin.tables.print') }}" target="_blank" class="pc-button-primary">
                Cetak semua QR
            </a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @forelse ($tables as $table)
                <article class="pc-card p-4 transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_24px_60px_rgba(69,36,14,0.12)]">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-stone-950">{{ $table->name }}</h3>
                            <p class="pc-subtle mt-1">{{ $table->code }} &middot; {{ $table->capacity }} kursi</p>
                        </div>
                        <span @class([
                            'pc-badge',
                            'pc-status-ready' => $table->is_active,
                            'pc-payment-refunded' => ! $table->is_active,
                        ])>
                            {{ $table->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <div class="mt-4 rounded-lg border border-amber-100 bg-white p-3 shadow-inner shadow-amber-900/5">
                        <img src="{{ route('admin.tables.qr', $table) }}" alt="QR {{ $table->name }}" class="mx-auto aspect-square w-full max-w-56">
                    </div>
                    <a href="{{ route('customer.menu', ['table' => $table->code]) }}" target="_blank" class="mt-3 flex min-h-11 items-center justify-between gap-3 rounded-lg bg-amber-50 px-3 py-2 text-sm font-extrabold text-amber-950 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-800">
                        <span class="truncate">Buka menu</span>
                        <span class="shrink-0 text-xs text-stone-500">{{ $table->code }}</span>
                    </a>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <a href="{{ route('admin.tables.qr', $table) }}" download="qr-{{ \Illuminate\Support\Str::slug($table->name) }}.svg" class="pc-button-secondary min-h-11 w-full whitespace-nowrap text-sm">
                            Export SVG
                        </a>
                        <form method="POST" action="{{ route('admin.tables.toggle', $table) }}">
                            @csrf
                            @method('PATCH')
                            <button class="pc-button-secondary min-h-11 w-full whitespace-nowrap text-sm">
                                {{ $table->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </div>
                    <details class="mt-3 rounded-lg border border-amber-100 bg-amber-50/70 p-3">
                        <summary class="cursor-pointer text-sm font-bold text-amber-900">Edit meja</summary>
                        <form method="POST" action="{{ route('admin.tables.update', $table) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                            @csrf
                            @method('PATCH')
                            <label class="pc-label">
                                Nama meja
                                <input name="name" required value="{{ $table->name }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Kapasitas
                                <input name="capacity" required type="number" min="1" max="20" value="{{ $table->capacity }}" class="pc-input">
                            </label>
                            <div class="sm:col-span-2">
                                <button class="pc-button-primary min-h-11 w-full">Simpan Meja</button>
                            </div>
                        </form>
                        <div class="mt-3 border-t border-amber-100 pt-3">
                            @if ($table->orders_count === 0)
                                <form method="POST" action="{{ route('admin.tables.destroy', $table) }}" onsubmit="return confirm('Hapus {{ $table->name }}? QR meja ini tidak bisa dipakai lagi.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        Hapus Meja
                                    </button>
                                </form>
                            @else
                                <p class="text-xs font-semibold leading-relaxed text-stone-500">Meja sudah punya histori order, jadi tidak bisa dihapus. Gunakan Nonaktifkan jika meja tidak dipakai lagi.</p>
                            @endif
                        </div>
                    </details>
                </article>
            @empty
                <p class="pc-card p-4 text-sm text-stone-500">Belum ada meja. Buat meja pertama untuk mendapatkan QR.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
