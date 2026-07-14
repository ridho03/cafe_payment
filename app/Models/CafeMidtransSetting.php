<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CafeMidtransSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'cafe_id',
        'mode',
        'merchant_id',
        'client_key',
        'server_key',
        'is_integrated',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'client_key' => 'encrypted',
            'server_key' => 'encrypted',
            'is_integrated' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function maskedClientKey(): string
    {
        return $this->maskSecret('client_key');
    }

    public function maskedServerKey(): string
    {
        return $this->maskSecret('server_key');
    }

    public function clientKey(): ?string
    {
        return $this->readEncryptedAttribute('client_key');
    }

    public function serverKey(): ?string
    {
        return $this->readEncryptedAttribute('server_key');
    }

    public function hasReadableKeys(): bool
    {
        return filled($this->clientKey()) && filled($this->serverKey());
    }

    public function hasUnreadableKeys(): bool
    {
        return $this->encryptedAttributeIsUnreadable('client_key')
            || $this->encryptedAttributeIsUnreadable('server_key');
    }

    public function isReady(): bool
    {
        return $this->is_integrated && $this->hasReadableKeys();
    }

    private function maskSecret(string $attribute): string
    {
        $value = $this->readEncryptedAttribute($attribute);

        if (! filled($value)) {
            return filled($this->getRawOriginal($attribute)) ? 'Perlu input ulang' : 'Belum diisi';
        }

        $length = strlen($value);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4).str_repeat('*', max(8, $length - 8)).substr($value, -4);
    }

    private function readEncryptedAttribute(string $attribute): ?string
    {
        try {
            return $this->getAttribute($attribute);
        } catch (\Throwable) {
            return null;
        }
    }

    private function encryptedAttributeIsUnreadable(string $attribute): bool
    {
        return filled($this->getRawOriginal($attribute)) && $this->readEncryptedAttribute($attribute) === null;
    }
}
