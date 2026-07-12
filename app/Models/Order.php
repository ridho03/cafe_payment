<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_FLOW = [
        'new' => 'Baru',
        'accepted' => 'Diterima',
        'preparing' => 'Diproses',
        'ready' => 'Siap',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
    ];

    public const PAYMENT_FLOW = [
        'unpaid' => 'Belum bayar',
        'paid' => 'Lunas',
        'failed' => 'Gagal',
        'refunded' => 'Refund',
    ];

    protected $fillable = [
        'cafe_table_id',
        'code',
        'customer_name',
        'customer_phone',
        'notes',
        'subtotal',
        'service_fee',
        'total',
        'status',
        'payment_status',
        'payment_method',
        'midtrans_order_id',
        'midtrans_snap_token',
        'midtrans_transaction_id',
        'midtrans_transaction_status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'cafe_table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_FLOW[$this->status] ?? ucfirst($this->status);
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_FLOW[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'new' => 'pc-status-new',
            'accepted' => 'pc-status-accepted',
            'preparing' => 'pc-status-preparing',
            'ready' => 'pc-status-ready',
            'completed' => 'pc-status-completed',
            'cancelled' => 'pc-status-cancelled',
            default => 'pc-status-new',
        };
    }

    public function paymentBadgeClass(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'pc-payment-unpaid',
            'paid' => 'pc-payment-paid',
            'failed' => 'pc-payment-failed',
            'refunded' => 'pc-payment-refunded',
            default => 'pc-payment-unpaid',
        };
    }
}
