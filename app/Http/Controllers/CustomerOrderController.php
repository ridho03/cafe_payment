<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\MidtransSnapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function __construct(private readonly MidtransSnapService $midtransSnap)
    {
    }

    public function show(CafeTable $table)
    {
        $table->loadMissing('cafe.midtransSetting');

        abort_unless($table->is_active, 404);

        $categories = MenuCategory::query()
            ->with(['items' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->where('cafe_id', $table->cafe_id)
            ->whereHas('items', fn ($query) => $query->where('is_available', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('customer.menu', compact('table', 'categories'));
    }

    public function store(Request $request, CafeTable $table)
    {
        $table->loadMissing('cafe.midtransSetting');

        abort_unless($table->is_active, 404);

        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:80'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:20'],
            'item_variants' => ['nullable', 'array'],
            'payment_method' => ['required', 'in:cash,midtrans_snap'],
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
            ->whereHas('category', fn ($query) => $query->where('cafe_id', $table->cafe_id))
            ->whereIn('id', $requestedItems->keys())
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== $requestedItems->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada menu yang sudah tidak tersedia. Muat ulang halaman dan coba lagi.',
            ]);
        }

        if ($validated['payment_method'] === 'midtrans_snap' && ! $this->midtransReady($table)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Cashless belum aktif untuk cafe ini. Pilih Cash atau hubungi kasir.',
            ]);
        }

        $order = DB::transaction(function () use ($validated, $requestedItems, $menuItems, $table) {
            $lineItems = $requestedItems
                ->map(function ($quantity, $itemId) use ($menuItems, $validated) {
                    $menuItem = $menuItems[(int) $itemId];
                    $selection = $menuItem->resolveVariantSelection($validated['item_variants'][$itemId] ?? null);
                    $unitPrice = $menuItem->price + $selection['price_delta'];

                    return [
                        'menu_item' => $menuItem,
                        'quantity' => $quantity,
                        'variant' => $selection['label'],
                        'unit_price' => $unitPrice,
                        'total' => $unitPrice * $quantity,
                    ];
                });

            $subtotal = $lineItems->sum('total');

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
                'payment_method' => $validated['payment_method'],
            ]);

            foreach ($lineItems as $lineItem) {
                $menuItem = $lineItem['menu_item'];
                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'name_snapshot' => $menuItem->name,
                    'variant' => $lineItem['variant'],
                    'price_snapshot' => $lineItem['unit_price'],
                    'quantity' => $lineItem['quantity'],
                    'total' => $lineItem['total'],
                ]);
            }

            return $order;
        });

        if ($order->payment_method === 'midtrans_snap') {
            try {
                $order->load(['items', 'table.cafe.midtransSetting']);
                $this->midtransSnap->ensureSnapToken($order);

                return redirect()->route('orders.status', $order);
            } catch (\Throwable $exception) {
                Log::error('Midtrans Snap token failed after checkout', [
                    'order_id' => $order->id,
                    'message' => $exception->getMessage(),
                ]);

                return redirect()
                    ->route('orders.status', $order)
                    ->withErrors('Pembayaran cashless belum bisa dibuka. Coba tekan tombol Bayar Cashless atau hubungi kasir.');
            }
        }

        return redirect()->route('orders.status', $order);
    }

    public function status(Order $order)
    {
        $order->load(['table.cafe.midtransSetting', 'items']);

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
            ->with('success', 'Pembayaran berhasil ditandai lunas.');
    }

    private function midtransReady(CafeTable $table): bool
    {
        $setting = $table->cafe?->midtransSetting;

        return (bool) $setting?->isReady();
    }
}
