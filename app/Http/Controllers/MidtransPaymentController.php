<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransPaymentController extends Controller
{
    public function createSnapToken(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.status', $order);
        }

        if (! $this->isConfigured()) {
            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY di file .env dulu.');
        }

        $this->configureMidtrans();
        $order->load(['items', 'table']);

        if (! $order->midtrans_order_id) {
            $order->update([
                'midtrans_order_id' => $order->code,
                'payment_method' => 'midtrans_snap',
            ]);
        }

        if (! $order->midtrans_snap_token) {
            $params = [
                'transaction_details' => [
                    'order_id' => $order->midtrans_order_id,
                    'gross_amount' => $order->total,
                ],
                'customer_details' => [
                    'first_name' => $order->customer_name ?: $order->table->name,
                    'phone' => $order->customer_phone,
                ],
                'item_details' => $this->itemDetails($order),
                'callbacks' => [
                    'finish' => route('orders.status', $order),
                    'unfinish' => route('orders.status', $order),
                    'error' => route('orders.status', $order),
                ],
            ];

            try {
                $order->update([
                    'midtrans_snap_token' => Snap::getSnapToken($params),
                ]);
            } catch (\Throwable $exception) {
                Log::error('Midtrans Snap token failed', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);

                return redirect()
                    ->route('orders.status', $order)
                    ->withErrors('Gagal membuat Snap token. Cek Server Key Midtrans sandbox kamu.');
            }
        }

        return redirect()
            ->route('orders.status', $order)
            ->with('success', 'Snap token siap. Klik tombol Bayar dengan Midtrans.');
    }

    public function notification(Request $request)
    {
        $payload = $request->all();
        $midtransOrderId = $payload['order_id'] ?? null;

        if (! $midtransOrderId) {
            return response()->json(['message' => 'order_id is required'], 422);
        }

        $order = Order::where('midtrans_order_id', $midtransOrderId)->firstOrFail();

        if (! $this->hasValidSignature($payload)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $this->updateOrderFromMidtransPayload($order, $payload);

        return response()->json(['message' => 'OK']);
    }

    public function syncStatus(Order $order)
    {
        if (! $order->midtrans_order_id) {
            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Order ini belum punya transaksi Midtrans.');
        }

        if (! $this->isConfigured()) {
            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Konfigurasi Midtrans belum lengkap.');
        }

        $this->configureMidtrans();

        try {
            $payload = (array) Transaction::status($order->midtrans_order_id);
            $this->updateOrderFromMidtransPayload($order, $payload);
        } catch (\Throwable $exception) {
            Log::error('Midtrans status sync failed', [
                'order_id' => $order->id,
                'midtrans_order_id' => $order->midtrans_order_id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Belum bisa mengambil status dari Midtrans. Coba beberapa detik lagi.');
        }

        return redirect()
            ->route('orders.status', $order)
            ->with('success', 'Status pembayaran sudah disinkronkan dari Midtrans.');
    }

    private function configureMidtrans(): void
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production');
        Config::$isSanitized = (bool) config('midtrans.is_sanitized');
        Config::$is3ds = (bool) config('midtrans.is_3ds');
    }

    private function isConfigured(): bool
    {
        return filled(config('midtrans.server_key')) && filled(config('midtrans.client_key'));
    }

    private function itemDetails(Order $order): array
    {
        $items = $order->items->map(fn ($item) => [
            'id' => 'menu-'.$item->id,
            'price' => $item->price_snapshot,
            'quantity' => $item->quantity,
            'name' => str($item->name_snapshot)->limit(50)->toString(),
        ])->values()->all();

        if ($order->service_fee > 0) {
            $items[] = [
                'id' => 'service-fee',
                'price' => $order->service_fee,
                'quantity' => 1,
                'name' => 'Biaya layanan',
            ];
        }

        return $items;
    }

    private function hasValidSignature(array $payload): bool
    {
        if (! filled(config('midtrans.server_key')) || ! isset($payload['signature_key'])) {
            return false;
        }

        $signature = hash(
            'sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            config('midtrans.server_key')
        );

        return hash_equals($signature, $payload['signature_key']);
    }

    private function updateOrderFromMidtransPayload(Order $order, array $payload): void
    {
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $paymentStatus = $this->paymentStatusFromMidtrans($transactionStatus, $fraudStatus);

        $updates = [
            'payment_status' => $paymentStatus,
            'midtrans_transaction_id' => $payload['transaction_id'] ?? $order->midtrans_transaction_id,
            'midtrans_transaction_status' => $transactionStatus,
            'paid_at' => $paymentStatus === 'paid' ? now() : $order->paid_at,
        ];

        if ($paymentStatus === 'paid' && $order->status === 'new') {
            $updates['status'] = 'accepted';
        }

        $order->update($updates);
    }

    private function paymentStatusFromMidtrans(?string $transactionStatus, ?string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'challenge' ? 'unpaid' : 'paid',
            'settlement' => 'paid',
            'pending' => 'unpaid',
            'deny', 'cancel', 'expire', 'failure' => 'failed',
            'refund', 'partial_refund' => 'refunded',
            default => 'unpaid',
        };
    }
}
