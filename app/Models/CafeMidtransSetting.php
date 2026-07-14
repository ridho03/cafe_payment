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
        return $this->maskSecret($this->client_key);
    }

    public function maskedServerKey(): string
    {
        return $this->maskSecret($this->server_key);
    }

    private function maskSecret(?string $value): string
    {
        if (! filled($value)) {
            return 'Belum diisi';
        }

        $length = strlen($value);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4).str_repeat('*', max(8, $length - 8)).substr($value, -4);
    }
}
