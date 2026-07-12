<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['table', 'items'])
            ->latest()
            ->paginate(20);

        return view('admin.orders', compact('orders'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Order::STATUS_FLOW))],
        ]);

        $order->update(['status' => $validated['status']]);

        return redirect()->route($this->ordersRoute())->with('success', 'Status pesanan berhasil diubah.');
    }

    public function updatePayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:'.implode(',', array_keys(Order::PAYMENT_FLOW))],
        ]);

        $updates = [
            'payment_status' => $validated['payment_status'],
            'paid_at' => $validated['payment_status'] === 'paid' ? now() : null,
        ];

        if ($validated['payment_status'] === 'paid' && $order->status === 'new') {
            $updates['status'] = 'accepted';
        }

        $order->update($updates);

        return redirect()->route($this->ordersRoute())->with('success', 'Status pembayaran berhasil diubah.');
    }

    private function ordersRoute(): string
    {
        return auth()->user()?->hasRole('cashier') ? 'cashier.orders' : 'admin.orders';
    }
}
