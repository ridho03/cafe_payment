@extends('layouts.app')

@section('title', 'Manajemen Akun')

@section('content')
@php
    $roles = ['admin' => 'Admin Cafe', 'cashier' => 'Kasir', 'kitchen' => 'Dapur'];
    $usersByCafe = $users->groupBy('cafe_id');
@endphp

<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Super Admin</p>
            <h1 class="pc-title">Manajemen Akun</h1>
            <p class="pc-subtle mt-2">Akun dipisahkan berdasarkan cafe. Pilih cafe dulu sebelum membuat admin, kasir, atau dapur.</p>
        </div>
        <a href="{{ route('super-admin.cafes') }}" class="pc-button-secondary">Kelola Cafe</a>
    </div>

    <section class="mt-6 grid gap-5 2xl:grid-cols-[400px_minmax(0,1fr)]">
        <article class="pc-card p-4">
            <div class="rounded-lg border border-amber-100 bg-white/70 p-3">
                <p class="text-xs font-extrabold uppercase text-amber-900">Akun baru</p>
                <h2 class="mt-1 font-bold text-stone-950">Buat akun cafe</h2>
                <p class="pc-subtle mt-1">Akun akan langsung masuk ke cafe yang dipilih.</p>
            </div>

            <form method="POST" action="{{ route('super-admin.accounts.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="pc-label">
                    Pilih cafe
                    <select name="cafe_id" required class="pc-input">
                        @foreach ($cafes as $cafe)
                            <option value="{{ $cafe->id }}" @selected(old('cafe_id') == $cafe->id)>{{ $cafe->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="pc-label">
                    Nama pengguna
                    <input name="name" required value="{{ old('name') }}" class="pc-input" placeholder="Nama pengguna">
                </label>
                <label class="pc-label">
                    Email login
                    <input name="email" required type="email" value="{{ old('email') }}" class="pc-input" placeholder="nama@domain.com">
                </label>
                <label class="pc-label">
                    Role
                    <select name="role" required class="pc-input">
                        @foreach ($roles as $role => $label)
                            <option value="{{ $role }}" @selected(old('role') === $role)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-1">
                    <label class="pc-label">
                        Password
                        <input name="password" required type="password" minlength="8" autocomplete="new-password" class="pc-input" placeholder="Minimal 8 karakter">
                    </label>
                    <label class="pc-label">
                        Konfirmasi password
                        <input name="password_confirmation" required type="password" minlength="8" autocomplete="new-password" class="pc-input">
                    </label>
                </div>
                <label class="pc-label flex min-h-11 items-center gap-2 rounded-lg border border-amber-100 bg-white/80 px-3 py-2">
                    <input name="is_active" value="1" type="checkbox" checked class="size-4 accent-amber-800">
                    <span class="whitespace-nowrap">Akun aktif</span>
                </label>
                <button class="pc-button-primary min-h-12 w-full">Buat Akun</button>
            </form>
        </article>

        <section class="min-w-0 space-y-4">
            @forelse ($cafes as $cafe)
                @php($cafeUsers = $usersByCafe->get($cafe->id, collect()))
                <article class="pc-card overflow-hidden">
                    <div class="pc-table-head flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="pc-wrap font-bold text-stone-950">{{ $cafe->name }}</h2>
                            <p class="pc-subtle">{{ $cafeUsers->count() }} akun terhubung</p>
                        </div>
                        <span class="pc-badge {{ $cafe->statusBadgeClass() }}">{{ $cafe->statusLabel() }}</span>
                    </div>

                    <div class="divide-y divide-amber-100">
                        @forelse ($cafeUsers as $user)
                            <details class="group">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 p-4 transition hover:bg-amber-50/70">
                                    <div class="min-w-0">
                                        <p class="pc-wrap font-bold text-stone-950">{{ $user->name }}</p>
                                        <p class="pc-subtle">{{ $user->email }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span @class([
                                            'pc-badge',
                                            'pc-status-completed' => $user->role === 'admin',
                                            'pc-payment-paid' => $user->role === 'cashier',
                                            'pc-status-preparing' => $user->role === 'kitchen',
                                        ])>{{ $roles[$user->role] ?? ucfirst($user->role) }}</span>
                                        <span class="pc-badge {{ $user->is_active ? 'pc-payment-paid' : 'pc-payment-failed' }}">
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                        <span class="pc-button-secondary min-h-10 px-3 py-1">Edit</span>
                                    </div>
                                </summary>

                                <div class="grid min-w-0 gap-4 border-t border-amber-100 bg-white/55 p-4 xl:grid-cols-[minmax(0,1fr)_minmax(300px,360px)]">
                                    <div class="xl:col-span-2">
                                        <form method="POST" action="{{ route('super-admin.accounts.impersonate', $user) }}" class="rounded-lg border border-emerald-100 bg-emerald-50 p-3">
                                            @csrf
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-bold text-emerald-950">Cek tampilan cafe</p>
                                                    <p class="text-xs font-semibold text-emerald-800">Masuk sementara sebagai akun ini tanpa mengetahui password.</p>
                                                </div>
                                                <button class="pc-button-primary min-h-10 px-3 py-1">Masuk</button>
                                            </div>
                                        </form>
                                    </div>

                                    <form method="POST" action="{{ route('super-admin.accounts.update', $user) }}" class="grid min-w-0 gap-3 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
                                        @csrf
                                        @method('PATCH')
                                        <label class="pc-label">
                                            Pilih cafe
                                            <select name="cafe_id" required class="pc-input">
                                                @foreach ($cafes as $optionCafe)
                                                    <option value="{{ $optionCafe->id }}" @selected($user->cafe_id === $optionCafe->id)>{{ $optionCafe->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="pc-label">
                                            Role
                                            <select name="role" required class="pc-input">
                                                @foreach ($roles as $role => $label)
                                                    <option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="pc-label">
                                            Nama pengguna
                                            <input name="name" required value="{{ $user->name }}" class="pc-input">
                                        </label>
                                        <label class="pc-label">
                                            Email login
                                            <input name="email" required type="email" value="{{ $user->email }}" class="pc-input">
                                        </label>
                                        <label class="pc-label flex min-h-11 items-center gap-2 self-end rounded-lg border border-amber-100 bg-white/80 px-3 py-2">
                                            <input name="is_active" value="1" type="checkbox" @checked($user->is_active) class="size-4 accent-amber-800">
                                            <span class="whitespace-nowrap">Akun aktif</span>
                                        </label>
                                        <div class="flex items-end">
                                            <button class="pc-button-primary min-h-12 w-full">Simpan Akun</button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('super-admin.accounts.password', $user) }}" class="min-w-0 rounded-lg border border-amber-100 bg-white/85 p-4">
                                        @csrf
                                        @method('PATCH')
                                        <h3 class="font-bold text-stone-950">Reset password</h3>
                                        <p class="pc-subtle mt-1">Password lama tidak ditampilkan.</p>
                                        <label class="pc-label mt-3">
                                            Password baru
                                            <input name="password" required type="password" minlength="8" autocomplete="new-password" class="pc-input">
                                        </label>
                                        <label class="pc-label mt-3">
                                            Konfirmasi
                                            <input name="password_confirmation" required type="password" minlength="8" autocomplete="new-password" class="pc-input">
                                        </label>
                                        <button class="pc-button-secondary mt-4 min-h-12 w-full whitespace-nowrap">Reset Password</button>
                                    </form>

                                    <form method="POST" action="{{ route('super-admin.accounts.destroy', $user) }}" class="min-w-0 rounded-lg border border-red-100 bg-red-50/70 p-4 xl:col-start-2" onsubmit="return confirm('Hapus akun {{ $user->email }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <h3 class="font-bold text-red-900">Hapus akun</h3>
                                        <p class="mt-1 text-xs font-semibold leading-relaxed text-red-800">Akun yang dihapus tidak bisa login lagi. Buat akun baru jika akses dibutuhkan kembali.</p>
                                        <button class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            Hapus Akun
                                        </button>
                                    </form>
                                </div>
                            </details>
                        @empty
                            <p class="p-4 text-sm font-semibold text-stone-500">Belum ada akun untuk cafe ini.</p>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="pc-card p-4 text-sm font-semibold text-stone-600">Belum ada cafe. Buat cafe terlebih dahulu.</div>
            @endforelse
        </section>
    </section>
</div>
@endsection
