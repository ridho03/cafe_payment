@extends('layouts.app')

@section('title', 'Menu')
@section('auto_refresh', '60')

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
    $externalImageValue = fn ($url) => str_contains((string) $url, '/uploads/menu/') || str_starts_with((string) $url, 'uploads/menu/') ? '' : $url;
    $variantLabels = \App\Models\MenuItem::variantLabels();
    $customVariantValue = fn ($item) => collect($item->availableVariants())
        ->reject(fn ($variant) => array_key_exists($variant, $variantLabels))
        ->implode(', ');
@endphp

<div class="pc-page grid gap-6 lg:grid-cols-[360px_1fr]">
    <section class="pc-card h-fit p-4 lg:sticky lg:top-24 lg:max-h-[calc(100dvh-7rem)] lg:overflow-y-auto lg:overscroll-contain">
        <h1 class="font-display text-2xl leading-tight text-stone-950">Tambah Menu</h1>
        <p class="pc-subtle mt-1">Kelola foto, harga, dan ketersediaan menu dari satu tempat.</p>
        <form method="POST" action="{{ route('admin.menu.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
            @csrf
            <label class="pc-label">
                Kategori
                <select name="menu_category_id" class="pc-input">
                    <option value="">Buat kategori baru</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="pc-label">
                Nama kategori baru
                <input name="category_name" class="pc-input" placeholder="Contoh: Coffee">
            </label>
            <label class="pc-label">
                Nama menu
                <input name="name" required class="pc-input">
            </label>
            <label class="pc-label">
                Deskripsi
                <textarea name="description" rows="2" class="pc-input py-2"></textarea>
            </label>
            <label class="pc-label">
                Harga
                <input name="price" required type="number" min="1000" step="500" class="pc-input">
            </label>
            <fieldset class="rounded-lg border border-amber-100 bg-amber-50/60 p-3">
                <legend class="px-1 text-sm font-semibold text-stone-700">Varian</legend>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($variantLabels as $value => $label)
                        <label class="flex min-h-11 items-center gap-2 rounded-lg border border-amber-100 bg-white px-3 text-sm font-bold text-stone-700">
                            <input name="variants[]" type="checkbox" value="{{ $value }}" checked class="size-4 rounded border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs font-semibold text-stone-500">Kosongkan semua untuk menu tanpa varian, seperti food atau snack.</p>
            </fieldset>
            <label class="pc-label">
                Varian tambahan
                <input name="custom_variants" class="pc-input" placeholder="Contoh: Large, Less Sugar">
                <span class="mt-1 block text-xs font-semibold text-stone-500">Pisahkan dengan koma. Opsi ini akan muncul bersama Hot/Ice.</span>
            </label>
            <label class="pc-label">
                Upload foto
                <input name="image_upload" type="file" accept="image/*" class="pc-input py-2">
                <span class="mt-1 block text-xs font-semibold text-stone-500">JPG/PNG/WebP sampai 4 MB.</span>
            </label>
            <label class="pc-label">
                Atau URL foto
                <input name="image_url" type="url" class="pc-input" placeholder="Opsional">
            </label>
            <button class="pc-button-primary min-h-12 w-full">
                Simpan Menu
            </button>
        </form>
    </section>

    <section class="space-y-5">
        <div>
            <p class="pc-kicker">Menu digital</p>
            <h2 class="pc-title">Daftar Menu</h2>
        </div>

        @foreach ($categories as $category)
            <section class="pc-card overflow-hidden">
                <div class="border-b border-amber-100 p-4">
                    <h3 class="font-bold text-stone-950">{{ $category->name }}</h3>
                </div>
                <div class="divide-y divide-amber-100">
                    @forelse ($category->items as $item)
                        <article class="p-4">
                            <div class="grid gap-4 md:grid-cols-[96px_1fr_auto] md:items-start">
                                <div data-menu-image class="relative aspect-square overflow-hidden rounded-lg bg-amber-100">
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
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-bold text-stone-950">{{ $item->name }}</p>
                                        <span @class([
                                            'pc-badge',
                                            'pc-status-ready' => $item->is_available,
                                            'pc-payment-refunded' => ! $item->is_available,
                                        ])>{{ $item->is_available ? 'Tersedia' : 'Habis' }}</span>
                                    </div>
                                    <p class="pc-subtle mt-1">{{ $item->description ?: 'Tanpa deskripsi' }}</p>
                                    @if ($item->hasVariants())
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($item->availableVariants() as $variant)
                                                <span class="pc-badge border border-amber-200 bg-white text-amber-950">{{ \App\Models\MenuItem::variantLabel($variant) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="mt-2 pc-price">{{ $format($item->price) }}</p>
                                    @if ($item->order_items_count > 0)
                                        <p class="mt-2 text-xs font-semibold text-stone-500">Sudah pernah dipesan, menu tidak bisa dihapus agar histori transaksi tetap aman.</p>
                                    @endif
                                </div>

                                <div class="grid gap-2 sm:grid-cols-3 md:w-36 md:grid-cols-1">
                                    <form method="POST" action="{{ route('admin.menu.toggle', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button @class([
                                            'inline-flex min-h-11 w-full items-center justify-center rounded-lg border px-4 py-2 text-sm font-bold transition focus:outline-none focus:ring-2',
                                            'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 focus:ring-emerald-500' => $item->is_available,
                                            'border-stone-200 bg-stone-50 text-stone-600 hover:bg-stone-100 focus:ring-stone-400' => ! $item->is_available,
                                        ])>
                                            {{ $item->is_available ? 'Tersedia' : 'Habis' }}
                                        </button>
                                    </form>

                                    @if ($item->order_items_count === 0)
                                        <form method="POST" action="{{ route('admin.menu.destroy', $item) }}" onsubmit="return confirm('Hapus menu {{ $item->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <details class="mt-4 rounded-lg border border-amber-100 bg-amber-50/70 p-3">
                                <summary class="cursor-pointer text-sm font-bold text-amber-900">Edit detail menu</summary>
                                <form method="POST" action="{{ route('admin.menu.update', $item) }}" enctype="multipart/form-data" class="mt-3 grid gap-3 md:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <label class="pc-label">
                                        Kategori
                                        <select name="menu_category_id" required class="pc-input">
                                            @foreach ($categories as $option)
                                                <option value="{{ $option->id }}" @selected($item->menu_category_id === $option->id)>{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="pc-label">
                                        Nama menu
                                        <input name="name" required value="{{ $item->name }}" class="pc-input">
                                    </label>
                                    <label class="pc-label md:col-span-2">
                                        Deskripsi
                                        <textarea name="description" rows="2" class="pc-input py-2">{{ $item->description }}</textarea>
                                    </label>
                                    <label class="pc-label">
                                        Harga
                                        <input name="price" required type="number" min="1000" step="500" value="{{ $item->price }}" class="pc-input">
                                    </label>
                                    <fieldset class="rounded-lg border border-amber-100 bg-white p-3">
                                        <legend class="px-1 text-sm font-semibold text-stone-700">Varian</legend>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            @foreach ($variantLabels as $value => $label)
                                                <label class="flex min-h-11 items-center gap-2 rounded-lg border border-amber-100 bg-amber-50/70 px-3 text-sm font-bold text-stone-700">
                                                    <input name="variants[]" type="checkbox" value="{{ $value }}" @checked(in_array($value, $item->availableVariants(), true)) class="size-4 rounded border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                        <label class="pc-label mt-3">
                                            Varian tambahan
                                            <input name="custom_variants" value="{{ $customVariantValue($item) }}" class="pc-input" placeholder="Contoh: Large, Less Sugar">
                                            <span class="mt-1 block text-xs font-semibold text-stone-500">Pisahkan dengan koma. Kosongkan jika hanya memakai Hot/Ice.</span>
                                        </label>
                                    </fieldset>
                                    <label class="pc-label">
                                        Upload foto baru
                                        <input name="image_upload" type="file" accept="image/*" class="pc-input py-2">
                                    </label>
                                    <label class="pc-label md:col-span-2">
                                        URL foto eksternal
                                        <input name="image_url" type="url" value="{{ $externalImageValue($item->image_url) }}" class="pc-input" placeholder="Kosongkan jika memakai upload">
                                    </label>
                                    <label class="flex min-h-11 items-center gap-3 text-sm font-bold text-stone-700 md:col-span-2">
                                        <input name="remove_image" type="checkbox" value="1" class="size-4 rounded border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800">
                                        Kosongkan foto menu
                                    </label>
                                    <div class="flex flex-wrap gap-2 md:col-span-2">
                                        <button class="pc-button-primary">
                                            Simpan Perubahan
                                        </button>
                                        <span class="inline-flex min-h-11 items-center text-sm font-semibold text-stone-500">
                                            Upload baru akan mengganti foto lama.
                                        </span>
                                    </div>
                                </form>
                            </details>
                        </article>
                    @empty
                        <p class="p-4 text-sm text-stone-500">Belum ada menu di kategori ini.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </section>
</div>
@endsection
