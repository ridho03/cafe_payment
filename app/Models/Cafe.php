<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cafe extends Model
{
    use HasFactory, HasPublicId;

    public const STATUSES = [
        'active' => 'Aktif',
        'demo' => 'Trial',
        'suspend' => 'Suspend',
        'expired' => 'Expired',
    ];

    protected $fillable = [
        'name',
        'public_id',
        'slug',
        'logo_path',
        'address',
        'contact_phone',
        'contact_email',
        'domain',
        'subdomain',
        'status',
        'active_from',
        'active_until',
    ];

    protected function casts(): array
    {
        return [
            'active_from' => 'date',
            'active_until' => 'date',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(CafeTable::class);
    }

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, CafeTable::class, 'cafe_id', 'cafe_table_id');
    }

    public function midtransSetting(): HasOne
    {
        return $this->hasOne(CafeMidtransSetting::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'pc-payment-paid',
            'demo' => 'pc-payment-unpaid',
            'suspend' => 'pc-payment-failed',
            'expired' => 'pc-payment-refunded',
            default => 'pc-status-new',
        };
    }

    public function daysUntilExpired(): ?int
    {
        if (! $this->active_until) {
            return null;
        }

        return (int) today()->diffInDays($this->active_until, false);
    }

    public function expiresSoon(int $days = 7): bool
    {
        $remaining = $this->daysUntilExpired();

        return $remaining !== null && $remaining >= 0 && $remaining <= $days;
    }

    public function isPastActiveUntil(): bool
    {
        return $this->daysUntilExpired() !== null && $this->daysUntilExpired() < 0;
    }

    public function expiryLabel(): string
    {
        $remaining = $this->daysUntilExpired();

        if ($remaining === null) {
            return 'Tanpa expired';
        }

        if ($remaining < 0) {
            return 'Expired '.abs($remaining).' hari lalu';
        }

        if ($remaining === 0) {
            return 'Berakhir hari ini';
        }

        if ($remaining <= 7) {
            return 'H-'.$remaining.' expired';
        }

        return 'Aktif sampai '.$this->active_until->format('d M Y');
    }

    public function expiryBadgeClass(): string
    {
        if ($this->isPastActiveUntil() || $this->status === 'expired') {
            return 'pc-payment-failed';
        }

        if ($this->expiresSoon(3)) {
            return 'pc-payment-unpaid';
        }

        if ($this->expiresSoon(7)) {
            return 'pc-status-accepted';
        }

        return 'pc-payment-refunded';
    }
}
