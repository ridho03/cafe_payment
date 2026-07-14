<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function ($model): void {
            if (! $model->public_id) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKey()
    {
        $publicId = $this->getAttribute('public_id');

        if (! $publicId && $this->exists) {
            $publicId = (string) Str::uuid();

            $this->forceFill(['public_id' => $publicId])->saveQuietly();
        }

        return $publicId;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
