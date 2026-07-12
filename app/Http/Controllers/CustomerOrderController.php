<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function show(CafeTable $table)
    {
        abort_unless($table->is_active, 404);

        $categories = MenuCategory::query()
            ->with(['items' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->whereHas('items', fn ($query) => $query->where('is_available', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('customer.menu', compact('table', 'categories'));
    }

    public function store(Request $request, CafeTable $table)
    {
        abort_unless($table->is_active, 404);

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:80'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:20'],
            'item_variants' => ['nullable', 'array'],
            'item_variants.*' => ['nullable', 'string', 'max:40'],
        ]);

        $requestedItems = collect($validated['items'])
            ->map(fn ($quantity) => (int) $quantity)
            ->filter(fn ($quantity) => $quantity > 0);

        if ($requestedItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Pilih minimal satu menu sebelum checkout.',
            ]);
        }

        $menuItems = MenuItem::query()
            ->whereIn('id', $requestedItems->keys())
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== $requestedItems->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada menu yang sudah tidak tersedia. Muat ulang halaman dan coba lagi.',
            ]);
        }

        $order = DB::transaction(function () use ($validated, $requestedItems, $menuItems, $table) {
            $subtotal = $requestedItems
                ->map(fn ($quantity, $itemId) => $menuItems[(int) $itemId]->price * $quantity)
                ->sum();

            $serviceFee = (int) round($subtotal * 0.05);

            $order = Order::create([
                'cafe_table_id' => $table->id,
                'code' => 'ORD-'.now()->format('His').'-'.Str::upper(Str::random(4)),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'total' => $subtotal + $serviceFee,
                'status' => 'new',
                'payment_status' => 'unpaid',
                'payment_method' => 'demo_qris',
            ]);

            foreach ($requestedItems as $itemId => $quantity) {
                $menuItem = $menuItems[(int) $itemId];
                $variants = $menuItem->availableVariants();
                $requestedVariant = $validated['item_variants'][$itemId] ?? null;
                $variant = in_array($requestedVariant, $variants, true)
                    ? $requestedVariant
                    : ($variants[0] ?? null);

                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'name_snapshot' => $menuItem->name,
                    'variant' => $variant,
                    'price_snapshot' => $menuItem->price,
                    'quantity' => $quantity,
                    'total' => $menuItem->price * $quantity,
                ]);
            }

            return $order;
        });

        return redirect()->route('orders.status', $order);
    }

    public function status(Order $order)
    {
        $order->load(['table', 'items']);

        return view('customer.status', compact('order'));
    }

    public function simulatePayment(Order $order)
    {
        if ($order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status' => $order->status === 'new' ? 'accepted' : $order->status,
                'paid_at' => now(),
            ]);
        }

        return redirect()
            ->route('orders.status', $order)
            ->with('success', 'Pembayaran demo berhasil. Nanti tombol ini bisa diganti callback Midtrans/Xendit.');
    }
}
