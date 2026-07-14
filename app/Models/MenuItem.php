<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'menu_category_id',
        'public_id',
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
        return count($this->availableVariantGroups()) > 0;
    }

    public function availableVariants(): array
    {
        return collect($this->availableVariantGroups())
            ->flatMap(fn (array $group) => collect($group['options'])->pluck('value'))
            ->values()
            ->all();
    }

    public function availableVariantGroups(): array
    {
        return self::normalizeVariantGroups($this->variants ?? []);
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

        if (str_contains($variant, ':') || str_contains($variant, ',')) {
            return str($variant)->squish()->toString();
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

    public static function normalizeVariantGroups(array|string|null $variants, ?string $customVariants = null, array $priceDeltas = []): array
    {
        $groups = [];
        $rawVariants = is_array($variants) ? $variants : (filled($variants) ? [$variants] : []);

        if (self::isGroupedVariantPayload($rawVariants)) {
            foreach ($rawVariants as $group) {
                $groups[] = self::normalizeVariantGroup(
                    $group['name'] ?? 'Pilihan',
                    $group['options'] ?? []
                );
            }
        } else {
            $flatVariants = self::normalizeVariants($rawVariants);
            $temperatureValues = array_values(array_intersect(['hot', 'ice'], $flatVariants));
            $otherValues = array_values(array_filter(
                $flatVariants,
                fn (string $variant) => ! in_array($variant, ['hot', 'ice'], true)
            ));

            if ($temperatureValues) {
                $groups[] = self::normalizeVariantGroup(
                    'Suhu',
                    collect($temperatureValues)
                        ->map(fn (string $value) => [
                            'value' => $value,
                            'label' => self::variantLabel($value),
                            'price_delta' => self::priceDeltaFor($priceDeltas, 'Suhu', $value),
                        ])
                        ->all()
                );
            }

            if ($otherValues) {
                $groups[] = self::normalizeVariantGroup(
                    'Pilihan',
                    collect($otherValues)
                        ->map(fn (string $value) => [
                            'value' => $value,
                            'label' => self::variantLabel($value),
                            'price_delta' => self::priceDeltaFor($priceDeltas, 'Pilihan', $value),
                        ])
                        ->all()
                );
            }
        }

        foreach (self::parseCustomVariantGroups($customVariants) as $customGroup) {
            $groups[] = self::normalizeVariantGroup($customGroup['name'], $customGroup['options']);
        }

        return collect($groups)
            ->map(fn (array $group) => self::normalizeVariantGroup($group['name'], $group['options']))
            ->filter(fn (array $group) => count($group['options']) > 0)
            ->unique(fn (array $group) => str($group['name'])->lower()->toString())
            ->take(6)
            ->values()
            ->all();
    }

    public function resolveVariantSelection(array|string|null $requested): array
    {
        $groups = $this->availableVariantGroups();
        $requestedValues = is_array($requested) ? $requested : ['0' => $requested];
        $labels = [];
        $priceDelta = 0;
        $values = [];

        foreach ($groups as $groupIndex => $group) {
            $requestedValue = $requestedValues[$groupIndex]
                ?? $requestedValues[$group['name']]
                ?? null;

            $option = collect($group['options'])
                ->first(fn (array $candidate) => $candidate['value'] === $requestedValue)
                ?? ($group['options'][0] ?? null);

            if (! $option) {
                continue;
            }

            $labels[] = $group['name'].': '.$option['label'];
            $priceDelta += (int) $option['price_delta'];
            $values[$group['name']] = $option['value'];
        }

        return [
            'label' => $labels ? implode(', ', $labels) : null,
            'price_delta' => $priceDelta,
            'values' => $values,
        ];
    }

    public function variantGroupEditorValue(): string
    {
        return collect($this->availableVariantGroups())
            ->reject(fn (array $group) => $group['name'] === 'Suhu')
            ->map(function (array $group) {
                $options = collect($group['options'])
                    ->map(fn (array $option) => $option['label'].(((int) $option['price_delta']) !== 0 ? '='.$option['price_delta'] : ''))
                    ->implode(', ');

                return $group['name'].': '.$options;
            })
            ->implode(PHP_EOL);
    }

    private static function isGroupedVariantPayload(array $variants): bool
    {
        return collect($variants)->contains(
            fn ($variant) => is_array($variant) && array_key_exists('options', $variant)
        );
    }

    private static function normalizeVariantGroup(string $name, array $options): array
    {
        $groupName = str($name)->stripTags()->squish()->limit(30, '')->toString() ?: 'Pilihan';

        return [
            'name' => $groupName,
            'options' => collect($options)
                ->map(fn ($option) => self::normalizeVariantOption($option))
                ->filter()
                ->unique(fn (array $option) => str($option['value'])->lower()->toString())
                ->take(8)
                ->values()
                ->all(),
        ];
    }

    private static function normalizeVariantOption(mixed $option): ?array
    {
        if (is_array($option)) {
            $value = str((string) ($option['value'] ?? $option['label'] ?? $option['name'] ?? ''))
                ->stripTags()
                ->squish()
                ->limit(40, '')
                ->toString();
            $label = str((string) ($option['label'] ?? self::variantLabel($value)))
                ->stripTags()
                ->squish()
                ->limit(40, '')
                ->toString();

            if (! $value && $label) {
                $value = $label;
            }

            if (! $value) {
                return null;
            }

            if (in_array(str($value)->lower()->toString(), ['hot', 'ice'], true)) {
                $value = str($value)->lower()->toString();
                $label = self::variantLabel($value);
            }

            return [
                'value' => $value,
                'label' => $label ?: self::variantLabel($value),
                'price_delta' => max(0, (int) ($option['price_delta'] ?? 0)),
            ];
        }

        return self::parseVariantOption((string) $option);
    }

    private static function parseCustomVariantGroups(?string $customVariants): array
    {
        if (! filled($customVariants)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $customVariants) ?: [])
            ->flatMap(function (string $line) {
                $line = str($line)->stripTags()->squish()->toString();

                if (! $line) {
                    return [];
                }

                if (str_contains($line, ':')) {
                    [$groupName, $options] = explode(':', $line, 2);
                } else {
                    $groupName = 'Pilihan';
                    $options = $line;
                }

                return [[
                    'name' => $groupName,
                    'options' => collect(preg_split('/[,;]+/', $options) ?: [])
                        ->map(fn (string $option) => self::parseVariantOption($option))
                        ->filter()
                        ->values()
                        ->all(),
                ]];
            })
            ->values()
            ->all();
    }

    private static function parseVariantOption(string $option): ?array
    {
        $option = str($option)->stripTags()->squish()->toString();

        if (! $option) {
            return null;
        }

        $priceDelta = 0;

        if (preg_match('/^(.+?)(?:\s*(?:=|\+)\s*)([0-9][0-9.]*)$/', $option, $matches)) {
            $option = trim($matches[1]);
            $priceDelta = (int) str_replace('.', '', $matches[2]);
        }

        $value = str($option)->limit(40, '')->toString();

        if (! $value) {
            return null;
        }

        if (in_array(str($value)->lower()->toString(), ['hot', 'ice'], true)) {
            $value = str($value)->lower()->toString();
        }

        return [
            'value' => $value,
            'label' => self::variantLabel($value),
            'price_delta' => max(0, $priceDelta),
        ];
    }

    private static function priceDeltaFor(array $priceDeltas, string $group, string $value): int
    {
        return max(0, (int) ($priceDeltas[$group][$value] ?? 0));
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
