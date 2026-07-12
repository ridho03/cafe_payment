<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_category_id',
        'name',
        'description',
        'price',
        'image_url',
        'variants',
        'is_available',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'is_available' => 'boolean',
        ];
    }

    public function imageSrc(): ?string
    {
        if (! $this->image_url) {
            return null;
        }

        if (str_starts_with($this->image_url, 'http://') || str_starts_with($this->image_url, 'https://') || str_starts_with($this->image_url, '/')) {
            return $this->image_url;
        }

        return asset($this->image_url);
    }

    public function hasVariants(): bool
    {
        return count($this->availableVariants()) > 0;
    }

    public function availableVariants(): array
    {
        return self::normalizeVariants($this->variants ?? []);
    }

    public static function variantLabels(): array
    {
        return [
            'hot' => 'Hot',
            'ice' => 'Ice',
        ];
    }

    public static function variantLabel(?string $variant): string
    {
        if (! $variant) {
            return '';
        }

        return self::variantLabels()[$variant] ?? str($variant)
            ->replace(['_', '-'], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    public static function normalizeVariants(array|string|null $variants, ?string $customVariants = null): array
    {
        $items = collect(is_array($variants) ? $variants : [$variants]);

        if (filled($customVariants)) {
            $items = $items->merge(preg_split('/[\r\n,]+/', $customVariants) ?: []);
        }

        return $items
            ->map(fn ($variant) => str((string) $variant)->stripTags()->squish()->limit(40, '')->toString())
            ->filter()
            ->unique(fn ($variant) => str($variant)->lower()->toString())
            ->take(8)
            ->values()
            ->all();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
