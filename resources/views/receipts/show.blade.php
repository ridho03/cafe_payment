<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $order->code }}</title>
    <style>
        @page { size: {{ $paperWidth }}mm auto; margin: 0; }
        * { box-sizing: border-box; }
        :root {
            --receipt-width: {{ $paperWidth }}mm;
            --receipt-padding: {{ $receiptPadding }}mm;
        }
        body {
            margin: 0;
            background: #fff8ec;
            color: #261307;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: {{ $paperWidth === 58 ? 10 : 12 }}px;
            line-height: 1.35;
        }
        .toolbar {
            display: flex;
            gap: 8px;
            justify-content: center;
            padding: 16px;
        }
        button, a {
            min-height: 44px;
            border: 1px solid #edd4ad;
            border-radius: 6px;
            background: white;
            color: #261307;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 10px 14px;
            text-decoration: none;
        }
        .receipt {
            width: var(--receipt-width);
            margin: 0 auto 24px;
            background: white;
            padding: var(--receipt-padding);
            box-shadow: 0 18px 50px rgba(69, 36, 14, 0.12);
        }
        .center { text-align: center; }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: flex-start;
        }
        .row span {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .row span:last-child {
            flex-shrink: 0;
            text-align: right;
        }
        .line {
            border-top: 1px dashed #261307;
            margin: 10px 0;
        }
        .muted { color: #705846; }
        .bold { font-weight: 700; }
        .item { margin-bottom: 8px; }
        .item-name { overflow-wrap: anywhere; }
        @media print {
            html, body {
                width: var(--receipt-width);
                background: white;
            }
            body {
                margin: 0;
            }
            .toolbar { display: none; }
            .receipt {
                width: var(--receipt-width);
                margin: 0;
                padding: var(--receipt-padding);
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Cetak</button>
        <a href="{{ route('cashier.orders.receipt', ['order' => $order, 'paper' => 80]) }}">80mm</a>
        <a href="{{ route('cashier.orders.receipt', ['order' => $order, 'paper' => 58]) }}">58mm</a>
        <a href="{{ route('cashier.orders') }}">Kembali</a>
    </div>

    <main class="receipt">
        <section class="center">
            <h1 style="font-size:{{ $paperWidth === 58 ? 13 : 16 }}px;margin:0 0 4px;">{{ config('app.name') }}</h1>
            <p class="muted" style="margin:0;">{{ $order->table->name }}</p>
        </section>

        <div class="line"></div>

        <section>
            <div class="row">
                <span>Kode</span>
                <span class="bold">{{ $order->code }}</span>
            </div>
            <div class="row">
                <span>Waktu</span>
                <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="row">
                <span>Kasir</span>
                <span>{{ auth()->user()->name }}</span>
            </div>
            <div class="row">
                <span>Bayar</span>
                <span>{{ $order->paymentLabel() }}</span>
            </div>
        </section>

        <div class="line"></div>

        <section>
            @foreach ($order->items as $item)
                <div class="item">
                    <div class="bold item-name">{{ $item->name_snapshot }}</div>
                    @if ($item->variant)
                        <div class="muted">{{ \App\Models\MenuItem::variantLabel($item->variant) }}</div>
                    @endif
                    <div class="row">
                        <span>{{ $item->quantity }} x Rp {{ number_format($item->price_snapshot, 0, ',', '.') }}</span>
                        <span>Rp {{ number_format($item->total, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        <div class="line"></div>

        <section>
            <div class="row">
                <span>Subtotal</span>
                <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="row">
                <span>Layanan</span>
                <span>Rp {{ number_format($order->service_fee, 0, ',', '.') }}</span>
            </div>
            <div class="row bold" style="font-size:14px;margin-top:6px;">
                <span>Total</span>
                <span>Rp {{ number_format($order->total, 0, ',', '.') }}</span>
            </div>
        </section>

        <div class="line"></div>

        <section class="center">
            <p style="margin:0;">Terima kasih</p>
            <p class="muted" style="margin:4px 0 0;">Simpan struk ini sebagai bukti pembayaran.</p>
        </section>
    </main>
</body>
</html>
