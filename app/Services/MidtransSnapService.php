<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransSnapService
{
    public function isConfigured(Order $order): bool
    {
        $setting = $order->table?->cafe?->midtransSetting;

        return $setting?->is_integrated && filled($setting->server_key) && filled($setting->client_key);
    }

    public function configure(Order $order): void
    {
        $setting = $order->table?->cafe?->midtransSetting;

        Config::$serverKey = $setting?->server_key;
        Config::$isProduction = $setting?->mode === 'production';
        Config::$isSanitized = (bool) config('midtrans.is_sanitized');
        Config::$is3ds = (bool) config('midtrans.is_3ds');
    }

    public function ensureSnapToken(Order $order): void
    {
        $order->loadMissing(['items', 'table.cafe.midtransSetting']);

        $this->configure($order);

        if (! $order->midtrans_order_id) {
            $order->update([
                'midtrans_order_id' => $order->code,
                'payment_method' => 'midtrans_snap',
            ]);
        }

        if ($order->midtrans_snap_token) {
            return;
        }

        $order->update([
            'midtrans_snap_token' => Snap::getSnapToken([
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
            ]),
        ]);
    }

    private function itemDetails(Order $order): array
    {
        $items = $order->items->map(fn ($item) => [
            'id' => 'menu-'.$item->id,
            'price' => $item->price_snapshot,
            'quantity' => $item->quantity,
            'name' => str($item->name_snapshot.($item->variant ? ' - '.$item->variant : ''))->limit(50)->toString(),
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
}
