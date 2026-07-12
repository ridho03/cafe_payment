<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        $orders = Order::with(['table', 'items'])
            ->where('payment_status', 'paid')
            ->whereIn('status', ['accepted', 'preparing', 'ready'])
            ->orderByRaw("CASE status WHEN 'accepted' THEN 1 WHEN 'preparing' THEN 2 WHEN 'ready' THEN 3 ELSE 4 END")
            ->oldest()
            ->get();

        return view('kitchen.orders', compact('orders'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:preparing,ready,completed'],
        ]);

        abort_unless($order->payment_status === 'paid', 422);

        $order->update(['status' => $validated['status']]);

        return redirect()->route('kitchen.orders')->with('success', 'Status dapur berhasil diperbarui.');
    }
}
