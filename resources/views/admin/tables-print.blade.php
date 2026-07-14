@php
    $layout = request('layout') === 'label' ? 'label' : 'card';
    $perPage = $layout === 'label' ? 8 : 4;
    $pages = $tables->chunk($perPage);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak QR Meja</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        :root {
            --page-padding: 10mm;
            --grid-cols: 2;
            --grid-rows: 2;
            --gap: 6mm;
            --card-padding: 7mm;
            --qr-size: 62mm;
            --brand-size: 13px;
            --title-size: 28px;
            --body-size: 11px;
            --caption-size: 12px;
        }
        body[data-layout="label"] {
            --page-padding: 9mm;
            --grid-cols: 2;
            --grid-rows: 4;
            --gap: 4mm;
            --card-padding: 4mm;
            --qr-size: 35mm;
            --brand-size: 9px;
            --title-size: 17px;
            --body-size: 8px;
            --caption-size: 8px;
        }
        body {
            margin: 0;
            background: #f7f1e7;
            color: #1c120b;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border-bottom: 1px solid #ead7b7;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 12px 30px rgba(80, 48, 20, 0.08);
            backdrop-filter: blur(12px);
        }
        .toolbar button,
        .toolbar a {
            min-height: 44px;
            border: 1px solid #d9b875;
            border-radius: 8px;
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 800;
            padding: 10px 16px;
            text-decoration: none;
        }
        .toolbar button,
        .toolbar a.primary {
            background: #1c1917;
            color: #fff7ed;
        }
        .toolbar a.secondary {
            background: #fffaf0;
            color: #4a2d14;
        }
        .toolbar a[aria-current="page"] {
            border-color: #1c1917;
            background: #f4df9a;
            color: #1c120b;
        }
        .print-note {
            width: 100%;
            margin: 2px 0 0;
            color: #6d625a;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }
        .sheet {
            width: 210mm;
            height: 297mm;
            margin: 10mm auto;
            padding: var(--page-padding);
            background: #fffdfa;
            box-shadow: 0 18px 60px rgba(50, 29, 12, 0.16);
            break-after: page;
            page-break-after: always;
        }
        .sheet:last-child {
            break-after: auto;
            page-break-after: auto;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(var(--grid-cols), minmax(0, 1fr));
            grid-template-rows: repeat(var(--grid-rows), minmax(0, 1fr));
            gap: var(--gap);
            height: 100%;
        }
        .qr-card {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 0;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px dashed #8f7b5f;
            border-radius: 8px;
            padding: var(--card-padding);
            text-align: center;
        }
        .brand {
            margin: 0 0 1.5mm;
            color: #8a4b16;
            font-size: var(--brand-size);
            font-weight: 900;
            line-height: 1.1;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }
        .qr-card img.logo {
            display: block;
            width: calc(var(--qr-size) * 0.34);
            height: calc(var(--qr-size) * 0.34);
            margin: 0 auto 2mm;
            object-fit: contain;
        }
        .table-name {
            margin: 0;
            max-width: 100%;
            color: #1c120b;
            font-family: Georgia, "Times New Roman", serif;
            font-size: var(--title-size);
            font-weight: 900;
            line-height: 1;
            overflow-wrap: anywhere;
        }
        .meta {
            margin: 1.5mm 0 0;
            color: #6d625a;
            font-size: var(--body-size);
            font-weight: 800;
            line-height: 1.2;
        }
        .qr-card img {
            display: block;
            width: var(--qr-size);
            height: var(--qr-size);
            margin: 4mm auto;
        }
        .hint {
            margin: 0;
            color: #8a4b16;
            font-size: var(--caption-size);
            font-weight: 900;
            letter-spacing: 0.14em;
            line-height: 1.2;
            text-transform: uppercase;
        }
        .instruction {
            margin: 1mm 0 0;
            color: #4c4038;
            font-size: var(--body-size);
            font-weight: 800;
            line-height: 1.2;
        }
        .code-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 7mm;
            margin-top: 3mm;
            border: 1px solid #ead7b7;
            border-radius: 999px;
            background: #fff7ed;
            color: #4a2d14;
            font-size: var(--body-size);
            font-weight: 900;
            line-height: 1;
            padding: 1.5mm 4mm;
        }
        body[data-layout="label"] .instruction {
            display: none;
        }
        body[data-layout="label"] .brand {
            display: none;
        }
        body[data-layout="label"] .qr-card img.logo {
            width: 12mm;
            height: 12mm;
            margin-bottom: 1mm;
        }
        body[data-layout="label"] .code-pill {
            min-height: 5mm;
            margin-top: 1.8mm;
            padding: 1mm 3mm;
        }
        .empty {
            display: grid;
            min-height: 120mm;
            place-items: center;
            color: #6d625a;
            font-size: 16px;
            font-weight: 800;
            text-align: center;
        }
        @media print {
            html,
            body {
                width: 210mm;
                min-height: 297mm;
                background: #ffffff;
            }
            .toolbar {
                display: none;
            }
            .sheet {
                width: 210mm;
                height: 297mm;
                margin: 0;
                box-shadow: none;
            }
            .qr-card {
                border-color: #6d625a;
                border-radius: 0;
            }
        }
    </style>
</head>
<body data-layout="{{ $layout }}">
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print QR</button>
        <a href="{{ route('admin.tables.print', ['layout' => 'card']) }}" class="secondary" @if ($layout === 'card') aria-current="page" @endif>
            Kartu meja 4/A4
        </a>
        <a href="{{ route('admin.tables.print', ['layout' => 'label']) }}" class="secondary" @if ($layout === 'label') aria-current="page" @endif>
            Label kecil 8/A4
        </a>
        <a href="{{ route('admin.tables') }}" class="primary">Kembali</a>
        <p class="print-note">Saat dialog print muncul, pilih kertas A4, portrait, scale 100%, dan margin default/none.</p>
    </div>

    @forelse ($pages as $pageTables)
        <main class="sheet" aria-label="Lembar QR meja">
            <section class="grid">
                @foreach ($pageTables as $table)
                    <article class="qr-card">
                        <img src="{{ $appLogoUrl }}" alt="Logo {{ $table->cafe?->name ?: $panelBrandName }}" class="logo">
                        <p class="brand">{{ $table->cafe?->name ?: $panelBrandName }}</p>
                        <h1 class="table-name">{{ $table->name }}</h1>
                        <p class="meta">{{ $table->capacity }} kursi</p>
                        <img src="{{ route('admin.tables.qr', $table) }}" alt="QR {{ $table->name }}">
                        <p class="hint">Scan Menu</p>
                        <p class="instruction">Arahkan kamera ke QR untuk pesan dari meja ini.</p>
                        <p class="code-pill">{{ $table->code }}</p>
                    </article>
                @endforeach
            </section>
        </main>
    @empty
        <main class="sheet">
            <div class="empty">Belum ada meja aktif untuk dicetak.</div>
        </main>
    @endforelse
</body>
</html>
