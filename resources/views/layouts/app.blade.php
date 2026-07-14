<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" href="{{ $appLogoUrl }}">
    <link rel="apple-touch-icon" href="{{ $appLogoUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $isAppShell = request()->is('admin*') || request()->is('super-admin*') || request()->is('cashier*') || request()->is('kitchen*');
    $autoRefreshSeconds = (int) trim($__env->yieldContent('auto_refresh', '0'));
    $orderSignal = trim($__env->yieldContent('order_signal', ''));
    $orderSignalKey = trim($__env->yieldContent('order_signal_key', '')) ?: request()->path();
    $role = auth()->user()->role ?? 'admin';
    $isSuperAdminArea = request()->is('super-admin*') && auth()->user()?->isSuperAdmin();
    $shellBrandName = $isSuperAdminArea ? 'Super Admin' : $panelBrandName;
    $shellBrandSubtitle = $isSuperAdminArea ? 'Panel penyedia aplikasi' : 'Order QR, kasir, dan dapur';
    $homeRoute = match ($role) {
        'developer', 'super_admin' => 'super-admin.dashboard',
        'cashier' => 'cashier.orders',
        'kitchen' => 'kitchen.orders',
        default => 'admin.dashboard',
    };
    $nav = [];

    if (auth()->user()?->isSuperAdmin()) {
        $nav = [
            ['label' => 'Super Admin', 'route' => 'super-admin.dashboard'],
            ['label' => 'Kelola Cafe', 'route' => 'super-admin.cafes'],
            ['label' => 'Manajemen Akun', 'route' => 'super-admin.accounts'],
            ['label' => 'Midtrans Cafe', 'route' => 'super-admin.midtrans'],
            ['label' => 'Teknis', 'route' => 'super-admin.technical'],
        ];
    } elseif (auth()->user()?->hasRole('admin')) {
        $nav = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['label' => 'Admin Pesanan', 'route' => 'admin.orders'],
            ['label' => 'Laporan', 'route' => 'admin.reports'],
            ['label' => 'Kasir', 'route' => 'cashier.orders'],
            ['label' => 'Dapur', 'route' => 'kitchen.orders'],
            ['label' => 'Menu', 'route' => 'admin.menu'],
            ['label' => 'Meja QR', 'route' => 'admin.tables'],
        ];
    } elseif (auth()->user()?->hasRole('cashier')) {
        $nav = [
            ['label' => 'Kasir', 'route' => 'cashier.orders'],
            ['label' => 'Laporan', 'route' => 'cashier.reports'],
        ];
    } elseif (auth()->user()?->hasRole('kitchen')) {
        $nav = [
            ['label' => 'Dapur', 'route' => 'kitchen.orders'],
        ];
    }

    $activeNav = collect($nav)->first(fn ($item) => request()->routeIs($item['route']));
@endphp
<body class="min-h-dvh font-sans text-stone-950 antialiased" data-auto-refresh="{{ $autoRefreshSeconds }}" data-order-signal="{{ e($orderSignal) }}" data-order-signal-key="{{ e($orderSignalKey) }}">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-amber-800 focus:px-4 focus:py-2 focus:text-white" href="#main">
        Lewati ke konten
    </a>

    @if ($isAppShell)
        <header class="pc-mobile-topbar lg:hidden">
            <button type="button" data-sidebar-open class="grid size-11 place-items-center rounded-lg border border-amber-100 bg-white text-stone-950 shadow-sm transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-800" aria-label="Buka navigasi" aria-controls="admin-sidebar" aria-expanded="false">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <a href="{{ route($homeRoute) }}" class="pc-focus-link flex min-h-11 min-w-0 items-center gap-3">
                <span class="pc-brand-mark size-10">
                    <img src="{{ $appLogoUrl }}" alt="Logo {{ config('app.name') }}" class="pc-brand-logo">
                </span>
                <span class="min-w-0">
                    <span class="block truncate font-display text-lg leading-5 text-stone-950">{{ $shellBrandName }}</span>
                    <span class="block truncate text-xs font-bold text-stone-500">{{ $shellBrandSubtitle }}</span>
                </span>
            </a>
        </header>

        <div data-sidebar-scrim class="fixed inset-0 z-40 hidden bg-stone-950/50 backdrop-blur-sm lg:hidden"></div>

        <aside id="admin-sidebar" data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-amber-100 bg-[linear-gradient(180deg,#fff7ed_0%,#ffffff_38%,#f0fdf4_100%)] shadow-2xl shadow-stone-950/10 backdrop-blur-xl transition-transform duration-200 ease-out lg:translate-x-0 lg:shadow-none">
            <div class="flex min-h-20 items-center justify-between gap-3 border-b border-amber-100 bg-gradient-to-br from-stone-950 via-amber-950 to-emerald-950 px-4 text-amber-50">
                <a href="{{ route($homeRoute) }}" class="pc-focus-link flex min-h-11 min-w-0 items-center gap-3">
                    <span class="pc-brand-mark">
                        <img src="{{ $appLogoUrl }}" alt="Logo {{ config('app.name') }}" class="pc-brand-logo">
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate font-display text-xl leading-5 text-amber-50">{{ $shellBrandName }}</span>
                        <span class="block truncate text-xs font-bold text-amber-100/75">{{ $isSuperAdminArea ? 'Developer tools' : 'Premium cafe ops' }}</span>
                    </span>
                </a>
                <button type="button" data-sidebar-close class="grid size-10 place-items-center rounded-lg text-amber-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-amber-100 lg:hidden" aria-label="Tutup navigasi">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-3 py-4">
                <nav class="grid gap-1 text-sm" aria-label="Navigasi admin">
                    @foreach ($nav as $item)
                        <a href="{{ route($item['route']) }}" @class([
                            'inline-flex min-h-11 items-center rounded-lg px-3 py-2 font-extrabold transition duration-200 focus:outline-none focus:ring-2 focus:ring-amber-800',
                            'bg-gradient-to-r from-stone-950 via-amber-950 to-emerald-950 text-amber-50 shadow-sm shadow-stone-950/20' => request()->routeIs($item['route']),
                            'text-stone-600 hover:bg-amber-50 hover:text-amber-950' => ! request()->routeIs($item['route']),
                        ])>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                @if ($autoRefreshSeconds > 0)
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-900">
                        Sinkron otomatis tiap {{ $autoRefreshSeconds }} detik.
                    </div>
                @endif

                <button type="button" data-sound-toggle class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm font-extrabold text-amber-950 shadow-sm transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-800 lg:hidden">
                    Aktifkan suara
                </button>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="border-t border-amber-100 p-3">
                @csrf
                <button class="inline-flex min-h-11 w-full items-center justify-center rounded-lg px-3 py-2 font-extrabold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Logout
                </button>
            </form>
        </aside>

        <header class="fixed left-72 right-0 top-0 z-20 hidden min-h-20 items-center justify-between gap-4 border-b border-amber-100/80 bg-white/90 px-8 shadow-sm shadow-amber-900/5 backdrop-blur-xl lg:flex">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-normal text-amber-900">{{ $isSuperAdminArea ? 'Panel super admin' : 'Panel operasional' }}</p>
                <h1 class="font-display text-2xl leading-tight text-stone-950">{{ $activeNav['label'] ?? trim($__env->yieldContent('title', config('app.name'))) }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <div class="rounded-lg border border-amber-100 bg-amber-50/70 px-3 py-2 text-right text-xs font-bold text-stone-600">
                    <span class="block text-stone-950">{{ now()->format('H:i') }}</span>
                    <span>{{ now()->format('d M Y') }}</span>
                </div>
                @if ($autoRefreshSeconds > 0)
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-extrabold text-emerald-900">
                        Sync {{ $autoRefreshSeconds }}d
                    </div>
                @endif
                <button type="button" data-sound-toggle class="inline-flex min-h-11 items-center justify-center rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm font-extrabold text-amber-950 shadow-sm transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-800">
                    Aktifkan suara
                </button>
            </div>
        </header>
    @endif

    <main id="main" @class([
        'min-h-dvh',
        'pt-16 lg:pl-72 lg:pt-20' => $isAppShell,
    ])>
        @if ($isAppShell && $autoRefreshSeconds > 0)
            <div data-refresh-status class="fixed bottom-3 right-3 z-20 hidden rounded-lg border border-emerald-100 bg-white/90 px-3 py-2 text-xs font-bold text-emerald-900 shadow-lg shadow-stone-950/10 backdrop-blur sm:block">
                Sync background {{ $autoRefreshSeconds }}d
            </div>
        @endif

        @if ($isAppShell && $impersonator)
            <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-950 shadow-sm">
                    <span>Mode cek cafe aktif. Kamu sedang masuk sebagai {{ auth()->user()->name }}.</span>
                    <form method="POST" action="{{ route('impersonation.stop') }}">
                        @csrf
                        <button class="pc-button-secondary min-h-10 px-3 py-1">Kembali ke Super Admin</button>
                    </form>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm" role="status">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm" role="alert">
                    {{ $errors->first() }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        (() => {
            const sidebar = document.querySelector('[data-sidebar]');
            const scrim = document.querySelector('[data-sidebar-scrim]');
            const openButton = document.querySelector('[data-sidebar-open]');
            const closeButton = document.querySelector('[data-sidebar-close]');

            const setSidebar = (open) => {
                if (!sidebar || !scrim || !openButton) return;

                sidebar.classList.toggle('-translate-x-full', !open);
                scrim.classList.toggle('hidden', !open);
                sidebar.dataset.open = open ? 'true' : 'false';
                openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.classList.toggle('overflow-hidden', open);
            };

            openButton?.addEventListener('click', () => setSidebar(true));
            closeButton?.addEventListener('click', () => setSidebar(false));
            scrim?.addEventListener('click', () => setSidebar(false));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setSidebar(false);
            });

            const storage = {
                get(key) {
                    try { return window.localStorage.getItem(key); } catch (error) { return null; }
                },
                set(key, value) {
                    try { window.localStorage.setItem(key, value); } catch (error) {}
                },
            };

            const soundButtons = document.querySelectorAll('[data-sound-toggle]');
            const soundKey = 'paymentCafeSoundEnabled';
            const isSoundEnabled = () => storage.get(soundKey) === '1';
            const updateSoundButtons = () => {
                soundButtons.forEach((button) => {
                    button.textContent = isSoundEnabled() ? 'Suara aktif' : 'Aktifkan suara';
                    button.classList.toggle('border-emerald-200', isSoundEnabled());
                    button.classList.toggle('bg-emerald-50', isSoundEnabled());
                    button.classList.toggle('text-emerald-900', isSoundEnabled());
                });
            };
            const playNotificationSound = () => {
                if (!isSoundEnabled()) return;

                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;

                try {
                    const context = new AudioContext();
                    const gain = context.createGain();
                    gain.connect(context.destination);
                    gain.gain.setValueAtTime(0.0001, context.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.14, context.currentTime + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.55);

                    [0, 0.16].forEach((offset, index) => {
                        const oscillator = context.createOscillator();
                        oscillator.type = 'sine';
                        oscillator.frequency.setValueAtTime(index === 0 ? 880 : 660, context.currentTime + offset);
                        oscillator.connect(gain);
                        oscillator.start(context.currentTime + offset);
                        oscillator.stop(context.currentTime + offset + 0.13);
                    });

                    window.setTimeout(() => context.close(), 800);
                } catch (error) {}
            };

            soundButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    storage.set(soundKey, isSoundEnabled() ? '0' : '1');
                    updateSoundButtons();
                    playNotificationSound();
                });
            });
            updateSoundButtons();

            const processSignal = () => {
                const signal = document.body.dataset.orderSignal || '';
                if (!signal) return;

                const signalKey = `paymentCafeSignal:${document.body.dataset.orderSignalKey || location.pathname}`;
                const previousSignal = storage.get(signalKey);

                if (previousSignal && previousSignal !== signal) {
                    playNotificationSound();
                }

                storage.set(signalKey, signal);
            };
            processSignal();

            const formSnapshots = new WeakMap();
            const currentForms = () => Array.from(document.querySelectorAll('main form'));
            const formValue = (form) => new URLSearchParams(new FormData(form)).toString();
            const snapshotForms = () => currentForms().forEach((form) => formSnapshots.set(form, formValue(form)));
            snapshotForms();

            const isTypingElement = (element) => {
                if (!element) return false;

                return ['INPUT', 'SELECT', 'TEXTAREA'].includes(element.tagName) || element.isContentEditable;
            };
            const isFormDirty = () => currentForms().some((form) => formSnapshots.get(form) !== formValue(form));
            const seconds = Number(document.body.dataset.autoRefresh || 0);
            let refreshInFlight = false;
            let lastRefreshAt = 0;

            const setRefreshStatus = (message, tone = 'ready') => {
                const refreshStatus = document.querySelector('[data-refresh-status]');
                if (!refreshStatus) return;

                refreshStatus.textContent = message;
                refreshStatus.classList.toggle('border-emerald-100', tone !== 'error');
                refreshStatus.classList.toggle('text-emerald-900', tone !== 'error');
                refreshStatus.classList.toggle('border-red-100', tone === 'error');
                refreshStatus.classList.toggle('text-red-800', tone === 'error');
            };

            const refreshCurrentPage = async ({ force = false } = {}) => {
                if (refreshInFlight) return false;

                const active = document.activeElement;
                const userIsEditing = isTypingElement(active) || isFormDirty() || sidebar?.dataset.open === 'true';

                if (!force && (document.hidden || userIsEditing)) {
                    return false;
                }

                refreshInFlight = true;
                setRefreshStatus('Sinkron...');

                try {
                    const response = await fetch(window.location.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Payment-Cafe-Refresh': '1',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.redirected && response.url && response.url !== window.location.href) {
                        window.location.href = response.url;
                        return true;
                    }

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const nextDocument = parser.parseFromString(html, 'text/html');
                    const nextMain = nextDocument.querySelector('#main');
                    const currentMain = document.querySelector('#main');

                    if (!nextMain || !currentMain) {
                        throw new Error('Konten tidak lengkap');
                    }

                    document.title = nextDocument.title || document.title;
                    document.body.dataset.autoRefresh = nextDocument.body.dataset.autoRefresh || document.body.dataset.autoRefresh;
                    document.body.dataset.orderSignal = nextDocument.body.dataset.orderSignal || '';
                    document.body.dataset.orderSignalKey = nextDocument.body.dataset.orderSignalKey || document.body.dataset.orderSignalKey;
                    currentMain.innerHTML = nextMain.innerHTML;
                    snapshotForms();
                    processSignal();

                    lastRefreshAt = Date.now();
                    setRefreshStatus(`Tersinkron ${new Date(lastRefreshAt).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`);

                    return true;
                } catch (error) {
                    setRefreshStatus('Sync gagal', 'error');
                    return false;
                } finally {
                    refreshInFlight = false;
                }
            };

            window.PaymentCafeRefresh = {
                refreshNow: () => refreshCurrentPage({ force: true }),
            };

            document.addEventListener('click', async (event) => {
                const payButton = event.target.closest('[data-midtrans-pay]');
                if (!payButton || !window.snap) return;

                const syncUrl = payButton.dataset.syncUrl;
                const snapToken = payButton.dataset.snapToken;

                if (!syncUrl || !snapToken) return;

                const syncMidtransStatus = async () => {
                    await fetch(syncUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    await refreshCurrentPage({ force: true });
                };

                window.snap.pay(snapToken, {
                    onSuccess: syncMidtransStatus,
                    onPending: syncMidtransStatus,
                    onError: syncMidtransStatus,
                    onClose: syncMidtransStatus,
                });
            });

            if (seconds > 0) {
                window.setInterval(() => {
                    refreshCurrentPage();
                }, seconds * 1000);
            }
        })();
    </script>
</body>
</html>
