@extends('layouts.app')

@section('title', 'Menu')
@section('auto_refresh', '60')

@section('content')
@php
    $format = fn ($amount) => 'Rp ' . number_format($amount, 0, ',', '.');
    $externalImageValue = fn ($url) => str_contains((string) $url, '/uploads/menu/') || str_starts_with((string) $url, 'uploads/menu/') ? '' : $url;
    $variantLabels = \App\Models\MenuItem::variantLabels();
    $hasVariantOption = fn ($item, $group, $value) => collect($item->availableVariantGroups())
        ->firstWhere('name', $group)
        ? collect(collect($item->availableVariantGroups())->firstWhere('name', $group)['options'])->contains(fn ($option) => $option['value'] === $value)
        : false;
    $variantPriceDelta = fn ($item, $group, $value) => (int) (collect(collect($item->availableVariantGroups())->firstWhere('name', $group)['options'] ?? [])
        ->firstWhere('value', $value)['price_delta'] ?? 0);
@endphp

<div class="pc-page grid gap-6 lg:grid-cols-[360px_1fr]">
    <section class="pc-card h-fit p-4 lg:sticky lg:top-24 lg:max-h-[calc(100dvh-7rem)] lg:overflow-y-auto lg:overscroll-contain">
        <h1 class="font-display text-2xl leading-tight text-stone-950">Tambah Menu</h1>
        <p class="pc-subtle mt-1">Kelola foto, harga, dan ketersediaan menu dari satu tempat.</p>
        <form method="POST" action="{{ route('admin.menu.categories.store') }}" class="mt-4 rounded-lg border border-amber-100 bg-white/70 p-3">
            @csrf
            <p class="text-sm font-bold text-stone-950">Kategori baru</p>
            <div class="mt-3 grid gap-3">
                <label class="pc-label">
                    Nama kategori
                    <input name="name" required maxlength="60" class="pc-input" placeholder="Contoh: Coffee">
                </label>
                <label class="pc-label">
                    Urutan
                    <input name="sort_order" type="number" min="0" max="65535" step="10" class="pc-input" placeholder="Otomatis">
                </label>
                <button class="pc-button-secondary min-h-11 w-full">Tambah Kategori</button>
            </div>
        </form>
        <div class="mt-5 border-t border-amber-100 pt-4">
            <h2 class="font-bold text-stone-950">Item menu baru</h2>
        </div>
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
                <legend class="px-1 text-sm font-semibold text-stone-700">Variasi suhu</legend>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach ($variantLabels as $value => $label)
                        <div class="rounded-lg border border-amber-100 bg-white p-3">
                            <label class="flex min-h-11 items-center gap-2 text-sm font-bold text-stone-700">
                                <input name="variants[]" type="checkbox" value="{{ $value }}" checked class="size-4 rounded border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800">
                                {{ $label }}
                            </label>
                            <label class="mt-2 block text-xs font-bold text-stone-500">
                                Tambah harga
                                <input name="variant_price_deltas[Suhu][{{ $value }}]" type="number" min="0" step="500" value="0" class="pc-input mt-1 h-10">
                            </label>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-xs font-semibold text-stone-500">Kosongkan semua untuk menu tanpa pilihan Hot/Ice.</p>
            </fieldset>
            <label class="pc-label">
                Grup variasi tambahan
                <textarea name="custom_variants" rows="3" class="pc-input py-2" placeholder="Ukuran: Regular, Large=5000&#10;Gula: Normal, Less Sugar"></textarea>
                <span class="mt-1 block text-xs font-semibold text-stone-500">Format: Nama variasi: Varian, Varian=TambahanHarga. Satu grup per baris.</span>
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

        @forelse ($categories as $category)
            <section class="pc-card overflow-hidden">
                <div class="border-b border-amber-100 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-stone-950">{{ $category->name }}</h3>
                            <p class="pc-subtle mt-1">{{ $category->items_count }} menu &middot; urutan {{ $category->sort_order }}</p>
                        </div>
                        <span class="pc-badge border border-amber-200 bg-white text-amber-950">Kategori</span>
                    </div>
                    <details class="mt-3 rounded-lg border border-amber-100 bg-amber-50/70 p-3">
                        <summary class="cursor-pointer text-sm font-bold text-amber-900">Atur kategori</summary>
                        <form method="POST" action="{{ route('admin.menu.categories.update', $category) }}" class="mt-3 grid gap-3 md:grid-cols-[minmax(0,1fr)_160px_auto] md:items-end">
                            @csrf
                            @method('PATCH')
                            <label class="pc-label">
                                Nama kategori
                                <input name="name" required maxlength="60" value="{{ $category->name }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Urutan
                                <input name="sort_order" required type="number" min="0" max="65535" value="{{ $category->sort_order }}" class="pc-input">
                            </label>
                            <button class="pc-button-primary min-h-11 whitespace-nowrap">Simpan</button>
                        </form>
                        <div class="mt-3 border-t border-amber-100 pt-3">
                            @if ($category->items_count === 0)
                                <form method="POST" action="{{ route('admin.menu.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 md:w-auto">
                                        Hapus Kategori
                                    </button>
                                </form>
                            @else
                                <p class="text-xs font-semibold leading-relaxed text-stone-500">Kategori masih berisi menu. Pindahkan atau hapus menu di bawah sebelum menghapus kategori.</p>
                            @endif
                        </div>
                    </details>
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
                                        <div class="mt-2 grid gap-2">
                                            @foreach ($item->availableVariantGroups() as $group)
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-extrabold uppercase text-stone-500">{{ $group['name'] }}</span>
                                                    @foreach ($group['options'] as $option)
                                                        <span class="pc-badge border border-amber-200 bg-white text-amber-950">
                                                            {{ $option['label'] }}@if($option['price_delta'] > 0) +{{ $format($option['price_delta']) }}@endif
                                                        </span>
                                                    @endforeach
                                                </div>
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
                                        <legend class="px-1 text-sm font-semibold text-stone-700">Variasi suhu</legend>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            @foreach ($variantLabels as $value => $label)
                                                <div class="rounded-lg border border-amber-100 bg-amber-50/70 p-3">
                                                    <label class="flex min-h-11 items-center gap-2 text-sm font-bold text-stone-700">
                                                        <input name="variants[]" type="checkbox" value="{{ $value }}" @checked($hasVariantOption($item, 'Suhu', $value)) class="size-4 rounded border-amber-300 text-amber-800 focus:ring-2 focus:ring-amber-800">
                                                        {{ $label }}
                                                    </label>
                                                    <label class="mt-2 block text-xs font-bold text-stone-500">
                                                        Tambah harga
                                                        <input name="variant_price_deltas[Suhu][{{ $value }}]" type="number" min="0" step="500" value="{{ $variantPriceDelta($item, 'Suhu', $value) }}" class="pc-input mt-1 h-10">
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                        <label class="pc-label mt-3">
                                            Grup variasi tambahan
                                            <textarea name="custom_variants" rows="3" class="pc-input py-2" placeholder="Ukuran: Regular, Large=5000&#10;Gula: Normal, Less Sugar">{{ $item->variantGroupEditorValue() }}</textarea>
                                            <span class="mt-1 block text-xs font-semibold text-stone-500">Contoh: Ukuran: Regular, Large=5000. Kosongkan jika hanya memakai Hot/Ice.</span>
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
        @empty
            <div class="pc-card p-4 text-sm font-semibold text-stone-600">Belum ada kategori. Tambahkan kategori dulu, lalu isi item menu.</div>
        @endforelse
    </section>
</div>
@endsection
