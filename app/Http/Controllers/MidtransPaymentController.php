<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransSnapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Transaction;

class MidtransPaymentController extends Controller
{
    public function __construct(private readonly MidtransSnapService $midtransSnap)
    {
    }

    public function createSnapToken(Order $order)
    {
        $order->load(['items', 'table.cafe.midtransSetting']);

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.status', $order);
        }

        if ($order->payment_method !== 'midtrans_snap') {
            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Order ini memilih pembayaran cash. Minta kasir menandai lunas setelah pembayaran diterima.');
        }

        if (! $this->midtransSnap->isConfigured($order)) {
            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Pembayaran cashless belum aktif untuk cafe ini.');
        }

        try {
            $this->midtransSnap->ensureSnapToken($order);
        } catch (\Throwable $exception) {
            Log::error('Midtrans Snap token failed', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Gagal membuka pembayaran cashless. Cek konfigurasi Server Key Midtrans.');
        }

        return redirect()->route('orders.status', $order);
    }

    public function notification(Request $request)
    {
        $payload = $request->all();
        $midtransOrderId = $payload['order_id'] ?? null;

        if (! $midtransOrderId) {
            return response()->json(['message' => 'order_id is required'], 422);
        }

        $order = Order::where('midtrans_order_id', $midtransOrderId)->firstOrFail();
        $order->load('table.cafe.midtransSetting');

        if (! $this->hasValidSignature($order, $payload)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $this->updateOrderFromMidtransPayload($order, $payload);

        return response()->json(['message' => 'OK']);
    }

    public function syncStatus(Order $order)
    {
        $order->load('table.cafe.midtransSetting');

        if (! $order->midtrans_order_id) {
            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Order ini belum punya transaksi cashless.');
        }

        if (! $this->midtransSnap->isConfigured($order)) {
            return redirect()
                ->route('orders.status', $order)
                ->withErrors('Pembayaran cashless belum aktif untuk cafe ini.');
        }

        $this->midtransSnap->configure($order);

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
                ->withErrors('Belum bisa mengambil status pembayaran. Coba beberapa detik lagi.');
        }

        return redirect()
            ->route('orders.status', $order)
            ->with('success', 'Status pembayaran sudah diperbarui.');
    }

    private function hasValidSignature(Order $order, array $payload): bool
    {
        $serverKey = $order->table?->cafe?->midtransSetting?->server_key;

        if (! filled($serverKey) || ! isset($payload['signature_key'])) {
            return false;
        }

        $signature = hash(
            'sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            $serverKey
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
