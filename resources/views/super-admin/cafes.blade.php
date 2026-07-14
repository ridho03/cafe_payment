@extends('layouts.app')

@section('title', 'Kelola Cafe')

@section('content')
<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Super Admin</p>
            <h1 class="pc-title">Kelola Cafe</h1>
            <p class="pc-subtle mt-2">Setiap cafe punya akun, meja, menu, order, laporan, dan struk sendiri.</p>
        </div>
        <a href="{{ route('super-admin.accounts') }}" class="pc-button-secondary">Kelola Akun</a>
    </div>

    <section class="mt-6 grid gap-5 2xl:grid-cols-[420px_minmax(0,1fr)]">
        <article class="pc-card p-4">
            <div class="rounded-lg border border-amber-100 bg-white/70 p-3">
                <p class="text-xs font-extrabold uppercase text-amber-900">Cafe baru</p>
                <h2 class="mt-1 font-bold text-stone-950">Tambah cafe</h2>
                <p class="pc-subtle mt-1">Setelah cafe dibuat, lanjut buat akun admin cafe di menu Manajemen Akun.</p>
            </div>

            <form method="POST" action="{{ route('super-admin.cafes.store') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <label class="pc-label">
                    Nama cafe
                    <input name="name" required value="{{ old('name') }}" class="pc-input" placeholder="Nama cafe">
                </label>
                <label class="pc-label">
                    Slug
                    <input name="slug" value="{{ old('slug') }}" class="pc-input" placeholder="otomatis jika kosong">
                </label>
                <label class="pc-label rounded-lg border border-dashed border-amber-300 bg-white/70 p-3">
                    Logo cafe
                    <span class="mt-1 block text-xs font-semibold text-stone-500">Opsional. Tampilan publik sekarang memakai logo utama aplikasi.</span>
                    <input name="logo" type="file" accept="image/*" class="pc-input py-2">
                </label>
                <fieldset class="rounded-lg border border-amber-100 bg-white/70 p-3">
                    <legend class="text-sm font-bold text-stone-700">Template awal</legend>
                    <p class="pc-subtle mt-1">Template hanya diterapkan saat cafe baru dibuat. Semua menu dan meja tetap dipisah per cafe.</p>
                    <div class="mt-3 grid max-h-[520px] gap-2 overflow-y-auto pr-1">
                        @foreach ($templates as $key => $template)
                            <label class="cursor-pointer">
                                <input type="radio" name="template_key" value="{{ $key }}" @checked(old('template_key', 'coffee_shop') === $key) class="peer sr-only">
                                <span class="block rounded-lg border border-amber-100 bg-white px-3 py-3 transition peer-checked:border-stone-950 peer-checked:bg-stone-950 peer-checked:text-amber-50">
                                    <span class="flex min-w-0 items-start justify-between gap-3">
                                        <span class="min-w-0">
                                            <span class="pc-wrap block font-extrabold">{{ $template['name'] }}</span>
                                            <span class="mt-1 block text-xs font-semibold leading-relaxed opacity-80">{{ $template['description'] }}</span>
                                        </span>
                                        <span class="shrink-0 rounded-full border border-current px-2 py-1 text-[11px] font-black opacity-80">{{ $template['summary'] }}</span>
                                    </span>
                                    <span class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach (($template['preview'] ?? []) as $preview)
                                            <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] font-black text-amber-950 peer-checked:bg-white/15 peer-checked:text-amber-50">{{ $preview }}</span>
                                        @endforeach
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="pc-label">
                    Alamat
                    <input name="address" value="{{ old('address') }}" class="pc-input" placeholder="Alamat cafe">
                </label>
                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-1">
                    <label class="pc-label">
                        Kontak telepon
                        <input name="contact_phone" value="{{ old('contact_phone') }}" class="pc-input" placeholder="08...">
                    </label>
                    <label class="pc-label">
                        Email kontak
                        <input name="contact_email" type="email" value="{{ old('contact_email') }}" class="pc-input" placeholder="admin@cafe.com">
                    </label>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-1">
                    <label class="pc-label">
                        Domain
                        <input name="domain" value="{{ old('domain') }}" class="pc-input" placeholder="cafe.com">
                    </label>
                    <label class="pc-label">
                        Subdomain
                        <input name="subdomain" value="{{ old('subdomain') }}" class="pc-input" placeholder="cafe.payment.com">
                    </label>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 2xl:grid-cols-1">
                    <label class="pc-label">
                        Status
                        <select name="status" required class="pc-input">
                            @foreach ($statuses as $status => $label)
                                <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="pc-label">
                        Tanggal mulai
                        <input name="active_from" type="date" value="{{ old('active_from', now()->toDateString()) }}" class="pc-input">
                    </label>
                    <label class="pc-label">
                        Aktif sampai
                        <input name="active_until" type="date" value="{{ old('active_until') }}" class="pc-input">
                    </label>
                </div>
                <button class="pc-button-primary min-h-12 w-full">Tambah Cafe</button>
            </form>
        </article>

        <section class="min-w-0 space-y-4">
            @forelse ($cafes as $cafe)
                <article class="pc-card overflow-hidden">
                    <details>
                        <summary class="pc-table-head flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-4 py-3 transition hover:bg-amber-100/60">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="pc-brand-mark size-12">
                                    <img src="{{ $appLogoUrl }}" alt="Logo {{ $cafe->name }}" class="pc-brand-logo">
                                </span>
                                <div class="min-w-0">
                                    <h2 class="pc-wrap font-bold text-stone-950">{{ $cafe->name }}</h2>
                                    <p class="pc-subtle">{{ $cafe->users_count }} akun &middot; {{ $cafe->tables_count }} meja &middot; {{ $cafe->menu_categories_count }} kategori &middot; {{ $cafe->domain ?: ($cafe->subdomain ?: 'domain belum diatur') }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="pc-badge {{ $cafe->statusBadgeClass() }}">{{ $cafe->statusLabel() }}</span>
                                <span class="pc-button-secondary min-h-10 px-3 py-1">Edit Cafe</span>
                            </div>
                        </summary>

                        <div class="border-t border-amber-100 bg-emerald-50/70 p-4">
                            <form method="POST" action="{{ route('super-admin.cafes.impersonate', $cafe) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-100 bg-white/85 p-3">
                                @csrf
                                <div>
                                    <p class="text-sm font-bold text-emerald-950">Cek sebagai Admin Cafe</p>
                                    <p class="text-xs font-semibold text-emerald-800">Masuk ke dashboard cafe ini memakai akun admin aktif pertama.</p>
                                </div>
                                <button class="pc-button-primary min-h-10 px-3 py-1">Masuk Cafe</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('super-admin.cafes.update', $cafe) }}" enctype="multipart/form-data" class="grid gap-4 border-t border-amber-100 bg-white/55 p-4 [grid-template-columns:repeat(auto-fit,minmax(230px,1fr))]">
                            @csrf
                            @method('PATCH')
                            <label class="pc-label">
                                Nama cafe
                                <input name="name" required value="{{ old('name', $cafe->name) }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Slug
                                <input name="slug" value="{{ old('slug', $cafe->slug) }}" class="pc-input">
                            </label>
                            <label class="pc-label rounded-lg border border-dashed border-amber-300 bg-white/70 p-3">
                                Logo cafe
                                <span class="mt-1 block text-xs font-semibold text-stone-500">Tersimpan sebagai data cafe, tetapi header dan halaman publik memakai logo utama aplikasi.</span>
                                <input name="logo" type="file" accept="image/*" class="pc-input py-2">
                            </label>
                            <label class="pc-label">
                                Alamat
                                <input name="address" value="{{ old('address', $cafe->address) }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Kontak telepon
                                <input name="contact_phone" value="{{ old('contact_phone', $cafe->contact_phone) }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Email kontak
                                <input name="contact_email" type="email" value="{{ old('contact_email', $cafe->contact_email) }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Domain
                                <input name="domain" value="{{ old('domain', $cafe->domain) }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Subdomain
                                <input name="subdomain" value="{{ old('subdomain', $cafe->subdomain) }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Status
                                <select name="status" required class="pc-input">
                                    @foreach ($statuses as $status => $label)
                                        <option value="{{ $status }}" @selected(old('status', $cafe->status) === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="pc-label">
                                Tanggal mulai
                                <input name="active_from" type="date" value="{{ old('active_from', $cafe->active_from?->toDateString()) }}" class="pc-input">
                            </label>
                            <label class="pc-label">
                                Aktif sampai
                                <input name="active_until" type="date" value="{{ old('active_until', $cafe->active_until?->toDateString()) }}" class="pc-input">
                            </label>
                            <label class="pc-label flex min-h-11 items-center gap-2 self-end rounded-lg border border-amber-100 bg-white/80 px-3 py-2">
                                <input name="remove_logo" value="1" type="checkbox" class="size-4 accent-amber-800">
                                <span class="whitespace-nowrap">Hapus logo cafe</span>
                            </label>
                            <div class="flex items-end sm:col-span-2">
                                <button class="pc-button-primary ml-auto min-h-12 w-full sm:w-auto">Simpan Perubahan</button>
                            </div>
                        </form>
                        <div class="border-t border-amber-100 bg-red-50/60 p-4">
                            @if ($cafe->orders_count > 0)
                                <div class="rounded-lg border border-red-100 bg-white/85 p-3">
                                    <p class="text-sm font-bold text-red-900">Cafe tidak bisa dihapus</p>
                                    <p class="mt-1 text-xs font-semibold leading-relaxed text-red-800">Cafe ini sudah memiliki {{ $cafe->orders_count }} transaksi. Gunakan status Suspend atau Expired agar histori order, laporan, dan struk tetap aman.</p>
                                </div>
                            @else
                                <form method="POST" action="{{ route('super-admin.cafes.destroy', $cafe) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-red-100 bg-white/85 p-3" onsubmit="return confirm('Hapus cafe {{ $cafe->name }} beserta akun, meja, dan menu awalnya?')">
                                    @csrf
                                    @method('DELETE')
                                    <div>
                                        <p class="text-sm font-bold text-red-900">Hapus cafe</p>
                                        <p class="mt-1 text-xs font-semibold leading-relaxed text-red-800">Aksi ini menghapus akun cafe, meja, menu, logo, dan pengaturan Midtrans yang belum punya transaksi.</p>
                                    </div>
                                    <button class="inline-flex min-h-11 items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        Hapus Cafe
                                    </button>
                                </form>
                            @endif
                        </div>
                    </details>
                </article>
            @empty
                <div class="pc-card p-4 text-sm font-semibold text-stone-600">Belum ada cafe. Tambahkan cafe pertama dari form di samping.</div>
            @endforelse
        </section>
    </section>
</div>
@endsection
