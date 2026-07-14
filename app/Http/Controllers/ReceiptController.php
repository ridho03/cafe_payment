<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $order->load(['table.cafe', 'items']);
        $this->ensureOrderBelongsToCurrentCafe($order);

        $paper = $request->query('paper', '80');
        $paperWidth = in_array($paper, ['58', '80'], true) ? (int) $paper : 80;
        $receiptPadding = $paperWidth === 58 ? 3 : 4;
        $cafe = $order->table->cafe ?: $request->user()?->cafe;

        return view('receipts.show', compact('order', 'paperWidth', 'receiptPadding', 'cafe'));
    }
}
