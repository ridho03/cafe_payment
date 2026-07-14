<?php

namespace App\Http\Controllers;

use App\Models\Cafe;

abstract class Controller
{
    protected function currentCafeId(): ?int
    {
        if (auth()->user()?->cafe_id) {
            return auth()->user()->cafe_id;
        }

        return Cafe::query()->orderBy('id')->value('id');
    }

    protected function ensureOrderBelongsToCurrentCafe($order): void
    {
        $order->loadMissing('table');

        abort_unless(! $this->currentCafeId() || $order->table?->cafe_id === $this->currentCafeId(), 403);
    }

    protected function ensureMenuItemBelongsToCurrentCafe($menuItem): void
    {
        $menuItem->loadMissing('category');

        abort_unless(! $this->currentCafeId() || $menuItem->category?->cafe_id === $this->currentCafeId(), 403);
    }
}
