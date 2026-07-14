@extends('layouts.app')

@section('title', 'Maintenance')
@section('auto_refresh', '60')

@section('content')
<div class="pc-page">
    <div class="pc-section-head">
        <div>
            <p class="pc-kicker">Developer tools</p>
            <h1 class="pc-title">Maintenance Aplikasi</h1>
            <p class="pc-subtle mt-2">Khusus developer/penyedia aplikasi. Admin cafe tidak punya akses ke halaman ini.</p>
        </div>
        <a href="{{ route('admin.maintenance.export-sql') }}" class="pc-button-primary">
            Export SQL
        </a>
    </div>

    <section class="mt-6 grid gap-4 lg:grid-cols-3">
        <article class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Backup database</h2>
            <p class="pc-subtle mt-2">Download file `.sql` berisi struktur dan data database aktif.</p>
            <a href="{{ route('admin.maintenance.export-sql') }}" class="pc-button-primary mt-4 w-full">
                Download SQL
            </a>
        </article>

        <article class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Cache aplikasi</h2>
            <p class="pc-subtle mt-2">Bersihkan cache config, route, view, dan cache aplikasi setelah deploy atau ubah `.env`.</p>
            <form method="POST" action="{{ route('admin.maintenance.cache.clear') }}" class="mt-4">
                @csrf
                <button class="pc-button-secondary w-full">
                    Clear Cache
                </button>
            </form>
        </article>

        <article class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Catatan aman</h2>
            <p class="pc-subtle mt-2">Jangan berikan akses maintenance ke admin cafe, kasir, atau dapur. Simpan backup SQL di tempat aman.</p>
            <div class="mt-4 rounded-lg border border-red-100 bg-red-50 p-3 text-sm font-bold text-red-800">
                Ganti password bawaan sebelum digunakan untuk transaksi.
            </div>
        </article>
    </section>

    <div class="mt-6 grid gap-5 lg:grid-cols-[420px_1fr]">
        <section class="pc-card p-4">
            <h2 class="font-bold text-stone-950">Tambah user internal</h2>
            <form method="POST" action="{{ route('admin.maintenance.users.store') }}" class="mt-4 space-y-3">
                @csrf
                <label class="pc-label">
                    Nama
                    <input name="name" required value="{{ old('name') }}" class="pc-input" placeholder="Nama user">
                </label>
                <label class="pc-label">
                    Email
                    <input name="email" required type="email" value="{{ old('email') }}" class="pc-input" placeholder="nama@domain.com">
                </label>
                <label class="pc-label">
                    Role
                    <select name="role" required class="pc-input">
                        <option value="admin" @selected(old('role') === 'admin')>Admin Cafe</option>
                        <option value="cashier" @selected(old('role') === 'cashier')>Kasir</option>
                        <option value="kitchen" @selected(old('role') === 'kitchen')>Dapur</option>
                        <option value="developer" @selected(old('role') === 'developer')>Developer/Penyedia</option>
                    </select>
                </label>
                <label class="pc-label">
                    Password
                    <input name="password" required type="password" minlength="8" class="pc-input" placeholder="Minimal 8 karakter">
                </label>
                <label class="pc-label">
                    Konfirmasi password
                    <input name="password_confirmation" required type="password" minlength="8" class="pc-input">
                </label>
                <button class="pc-button-primary min-h-12 w-full">
                    Buat User
                </button>
            </form>
        </section>

        <section class="space-y-5">
            <article class="pc-card overflow-hidden">
                <div class="pc-table-head px-4 py-3">
                    <h2 class="font-bold text-stone-950">User internal</h2>
                </div>
                <div class="divide-y divide-amber-100">
                    @foreach ($users as $user)
                        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-bold text-stone-950">{{ $user->name }}</p>
                                <p class="pc-subtle mt-1">{{ $user->email }}</p>
                            </div>
                            <span @class([
                                'pc-badge',
                                'pc-status-ready' => $user->role === 'developer',
                                'pc-status-completed' => $user->role === 'admin',
                                'pc-payment-paid' => $user->role === 'cashier',
                                'pc-status-preparing' => $user->role === 'kitchen',
                            ])>
                                {{ ['developer' => 'Developer', 'admin' => 'Admin Cafe', 'cashier' => 'Kasir', 'kitchen' => 'Dapur'][$user->role] ?? ucfirst($user->role) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="pc-card overflow-hidden">
                <div class="pc-table-head px-4 py-3">
                    <h2 class="font-bold text-stone-950">Info sistem</h2>
                </div>
                <dl class="grid gap-0 divide-y divide-amber-100">
                    @foreach ($system as $label => $value)
                        <div class="grid gap-1 p-4 sm:grid-cols-[180px_1fr]">
                            <dt class="text-sm font-extrabold capitalize text-stone-600">{{ str_replace('_', ' ', $label) }}</dt>
                            <dd class="break-all text-sm font-bold text-stone-950">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </article>
        </section>
    </div>
</div>
@endsection
