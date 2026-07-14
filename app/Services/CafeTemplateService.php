<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\CafeTable;
use App\Models\MenuCategory;
use Illuminate\Support\Str;

class CafeTemplateService
{
    public function templates(): array
    {
        return [
            'blank' => [
                'name' => 'Kosong',
                'description' => 'Cafe dibuat tanpa menu dan meja contoh.',
                'summary' => '0 menu, 0 meja',
                'preview' => ['Tanpa data contoh'],
            ],
            'coffee_shop' => [
                'name' => 'Coffee Shop',
                'description' => 'Kategori kopi, non kopi, snack, dan 6 meja awal.',
                'summary' => '9 menu, 6 meja',
                'preview' => ['Coffee', 'Non Coffee', 'Snack'],
            ],
            'warung_makan' => [
                'name' => 'Warung Makan',
                'description' => 'Menu nasi, mie, lauk harian, minuman, dan 8 meja awal.',
                'summary' => '12 menu, 8 meja',
                'preview' => ['Nasi & Lauk', 'Mie & Gorengan', 'Minuman'],
            ],
            'quick_resto' => [
                'name' => 'Resto Cepat',
                'description' => 'Makanan utama, minuman, dessert, dan 10 meja awal.',
                'summary' => '9 menu, 10 meja',
                'preview' => ['Makanan Utama', 'Minuman', 'Dessert'],
            ],
            'bakery' => [
                'name' => 'Bakery',
                'description' => 'Roti, pastry, cake slice, minuman, dan 5 meja awal.',
                'summary' => '10 menu, 5 meja',
                'preview' => ['Bread', 'Pastry', 'Cake & Drink'],
            ],
            'drink_booth' => [
                'name' => 'Booth Minuman',
                'description' => 'Es teh, kopi susu, topping, dan 4 meja/counter.',
                'summary' => '8 menu, 4 meja',
                'preview' => ['Tea Series', 'Coffee Milk', 'Topping'],
            ],
            'boba_dessert' => [
                'name' => 'Boba & Dessert',
                'description' => 'Milk tea, fruit tea, dessert cup, topping, dan 4 meja awal.',
                'summary' => '10 menu, 4 meja',
                'preview' => ['Milk Tea', 'Fruit Tea', 'Dessert Cup'],
            ],
        ];
    }

    public function keys(): array
    {
        return array_keys($this->templates());
    }

    public function apply(Cafe $cafe, string $templateKey): array
    {
        if ($templateKey === 'blank') {
            return ['categories' => 0, 'items' => 0, 'tables' => 0];
        }

        $definition = $this->definition($templateKey);
        $created = ['categories' => 0, 'items' => 0, 'tables' => 0];

        foreach ($definition['categories'] as $sort => $categoryData) {
            $category = MenuCategory::firstOrCreate(
                [
                    'cafe_id' => $cafe->id,
                    'name' => $categoryData['name'],
                ],
                ['sort_order' => ($sort + 1) * 10]
            );

            if ($category->wasRecentlyCreated) {
                $created['categories']++;
            }

            foreach ($categoryData['items'] as $index => $itemData) {
                $item = $category->items()->firstOrCreate(
                    ['name' => $itemData['name']],
                    [
                        'description' => $itemData['description'] ?? null,
                        'price' => $itemData['price'],
                        'variants' => $itemData['variants'] ?? [],
                        'is_available' => true,
                        'sort_order' => ($index + 1) * 10,
                    ]
                );

                if ($item->wasRecentlyCreated) {
                    $created['items']++;
                }
            }
        }

        foreach (range(1, $definition['tables']) as $number) {
            $code = $this->tableCode($cafe, $number);
            $table = CafeTable::firstOrCreate(
                [
                    'cafe_id' => $cafe->id,
                    'code' => $code,
                ],
                [
                    'name' => 'Meja '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                    'capacity' => $number <= 4 ? 2 : 4,
                    'is_active' => true,
                ]
            );

            if ($table->wasRecentlyCreated) {
                $created['tables']++;
            }
        }

        return $created;
    }

    private function definition(string $templateKey): array
    {
        return match ($templateKey) {
            'warung_makan' => [
                'tables' => 8,
                'categories' => [
                    [
                        'name' => 'Nasi & Lauk',
                        'items' => [
                            ['name' => 'Nasi Ayam Geprek', 'description' => 'Nasi hangat, ayam geprek, sambal bawang.', 'price' => 22000],
                            ['name' => 'Nasi Lele Goreng', 'description' => 'Lele goreng, lalapan, sambal.', 'price' => 20000],
                            ['name' => 'Nasi Telur Balado', 'description' => 'Telur balado dan sayur harian.', 'price' => 16000],
                            ['name' => 'Nasi Campur', 'description' => 'Nasi, lauk pilihan, sayur, sambal.', 'price' => 24000],
                        ],
                    ],
                    [
                        'name' => 'Mie & Gorengan',
                        'items' => [
                            ['name' => 'Mie Goreng Jawa', 'description' => 'Mie goreng bumbu manis gurih.', 'price' => 18000],
                            ['name' => 'Mie Rebus Special', 'description' => 'Mie kuah dengan telur dan sayur.', 'price' => 18000],
                            ['name' => 'Tempe Mendoan', 'description' => 'Tempe goreng tipis isi 4.', 'price' => 10000],
                            ['name' => 'Bakwan Sayur', 'description' => 'Bakwan hangat isi 4.', 'price' => 10000],
                        ],
                    ],
                    [
                        'name' => 'Minuman',
                        'items' => [
                            ['name' => 'Es Teh Manis', 'description' => 'Teh manis dingin.', 'price' => 6000],
                            ['name' => 'Es Jeruk', 'description' => 'Jeruk peras dingin.', 'price' => 10000],
                            ['name' => 'Kopi Hitam', 'description' => 'Kopi tubruk panas.', 'price' => 8000],
                            ['name' => 'Air Mineral', 'description' => 'Air mineral botol.', 'price' => 5000],
                        ],
                    ],
                ],
            ],
            'quick_resto' => [
                'tables' => 10,
                'categories' => [
                    [
                        'name' => 'Makanan Utama',
                        'items' => [
                            ['name' => 'Chicken Rice Bowl', 'description' => 'Nasi ayam saus gurih.', 'price' => 35000],
                            ['name' => 'Beef Rice Bowl', 'description' => 'Nasi sapi tumis dan sayur.', 'price' => 42000],
                            ['name' => 'Nasi Goreng Special', 'description' => 'Nasi goreng telur dan ayam.', 'price' => 32000],
                        ],
                    ],
                    [
                        'name' => 'Minuman',
                        'items' => [
                            ['name' => 'Es Teh Manis', 'description' => 'Teh dingin manis.', 'price' => 8000],
                            ['name' => 'Lemon Tea', 'description' => 'Teh lemon segar.', 'price' => 15000],
                            ['name' => 'Mineral Water', 'description' => 'Air mineral botol.', 'price' => 7000],
                        ],
                    ],
                    [
                        'name' => 'Dessert',
                        'items' => [
                            ['name' => 'Pudding Cokelat', 'description' => 'Pudding lembut rasa cokelat.', 'price' => 18000],
                            ['name' => 'Roti Bakar', 'description' => 'Roti bakar cokelat keju.', 'price' => 22000],
                            ['name' => 'Ice Cream Cup', 'description' => 'Es krim cup vanilla.', 'price' => 16000],
                        ],
                    ],
                ],
            ],
            'bakery' => [
                'tables' => 5,
                'categories' => [
                    [
                        'name' => 'Bread',
                        'items' => [
                            ['name' => 'Roti Cokelat', 'description' => 'Roti lembut isi cokelat.', 'price' => 12000],
                            ['name' => 'Roti Keju Susu', 'description' => 'Roti isi keju dan susu.', 'price' => 14000],
                            ['name' => 'Garlic Bread', 'description' => 'Roti panggang garlic butter.', 'price' => 16000],
                        ],
                    ],
                    [
                        'name' => 'Pastry',
                        'items' => [
                            ['name' => 'Butter Croissant', 'description' => 'Croissant butter flaky.', 'price' => 22000],
                            ['name' => 'Danish Raisin', 'description' => 'Pastry raisin manis.', 'price' => 20000],
                            ['name' => 'Sausage Roll', 'description' => 'Pastry sosis gurih.', 'price' => 24000],
                        ],
                    ],
                    [
                        'name' => 'Cake & Drink',
                        'items' => [
                            ['name' => 'Cheesecake Slice', 'description' => 'Potongan cheesecake lembut.', 'price' => 28000],
                            ['name' => 'Brownies Slice', 'description' => 'Brownies cokelat padat.', 'price' => 20000],
                            ['name' => 'Iced Latte', 'description' => 'Kopi susu dingin.', 'price' => 24000],
                            ['name' => 'Hot Tea', 'description' => 'Teh panas pilihan.', 'price' => 12000],
                        ],
                    ],
                ],
            ],
            'drink_booth' => [
                'tables' => 4,
                'categories' => [
                    [
                        'name' => 'Tea Series',
                        'items' => [
                            ['name' => 'Original Tea', 'description' => 'Teh original dingin.', 'price' => 8000, 'variants' => ['regular', 'large']],
                            ['name' => 'Milk Tea', 'description' => 'Teh susu creamy.', 'price' => 14000, 'variants' => ['regular', 'large']],
                            ['name' => 'Lemon Tea', 'description' => 'Teh lemon segar.', 'price' => 12000, 'variants' => ['regular', 'large']],
                        ],
                    ],
                    [
                        'name' => 'Coffee Milk',
                        'items' => [
                            ['name' => 'Kopi Susu Gula Aren', 'description' => 'Kopi susu dengan gula aren.', 'price' => 18000],
                            ['name' => 'Caramel Coffee', 'description' => 'Kopi susu caramel.', 'price' => 20000],
                        ],
                    ],
                    [
                        'name' => 'Topping',
                        'items' => [
                            ['name' => 'Boba', 'description' => 'Topping boba tambahan.', 'price' => 4000],
                            ['name' => 'Grass Jelly', 'description' => 'Topping cincau.', 'price' => 4000],
                            ['name' => 'Cheese Cream', 'description' => 'Cream cheese tambahan.', 'price' => 6000],
                        ],
                    ],
                ],
            ],
            'boba_dessert' => [
                'tables' => 4,
                'categories' => [
                    [
                        'name' => 'Milk Tea',
                        'items' => [
                            ['name' => 'Classic Boba Milk Tea', 'description' => 'Milk tea dengan brown sugar boba.', 'price' => 18000, 'variants' => ['regular', 'large']],
                            ['name' => 'Brown Sugar Fresh Milk', 'description' => 'Susu segar brown sugar dan boba.', 'price' => 22000, 'variants' => ['regular', 'large']],
                            ['name' => 'Taro Milk Tea', 'description' => 'Milk tea taro creamy.', 'price' => 20000, 'variants' => ['regular', 'large']],
                        ],
                    ],
                    [
                        'name' => 'Fruit Tea',
                        'items' => [
                            ['name' => 'Mango Tea', 'description' => 'Teh mangga segar.', 'price' => 17000, 'variants' => ['regular', 'large']],
                            ['name' => 'Lychee Tea', 'description' => 'Teh leci dengan buah.', 'price' => 18000, 'variants' => ['regular', 'large']],
                            ['name' => 'Strawberry Tea', 'description' => 'Teh strawberry manis segar.', 'price' => 17000, 'variants' => ['regular', 'large']],
                        ],
                    ],
                    [
                        'name' => 'Dessert Cup',
                        'items' => [
                            ['name' => 'Mango Sago', 'description' => 'Dessert mangga, sago, dan susu.', 'price' => 24000],
                            ['name' => 'Choco Pudding Cup', 'description' => 'Pudding cokelat cup.', 'price' => 18000],
                            ['name' => 'Cheese Cream', 'description' => 'Topping cream cheese tambahan.', 'price' => 6000],
                            ['name' => 'Extra Boba', 'description' => 'Tambahan boba brown sugar.', 'price' => 5000],
                        ],
                    ],
                ],
            ],
            default => [
                'tables' => 6,
                'categories' => [
                    [
                        'name' => 'Coffee',
                        'items' => [
                            ['name' => 'Americano', 'description' => 'Espresso dan air panas.', 'price' => 18000, 'variants' => ['hot', 'ice']],
                            ['name' => 'Cafe Latte', 'description' => 'Espresso dengan susu steamed.', 'price' => 26000, 'variants' => ['hot', 'ice']],
                            ['name' => 'Caramel Macchiato', 'description' => 'Kopi susu caramel.', 'price' => 32000, 'variants' => ['hot', 'ice']],
                        ],
                    ],
                    [
                        'name' => 'Non Coffee',
                        'items' => [
                            ['name' => 'Matcha Latte', 'description' => 'Matcha creamy dengan susu.', 'price' => 28000, 'variants' => ['hot', 'ice']],
                            ['name' => 'Chocolate Signature', 'description' => 'Cokelat pekat dengan susu.', 'price' => 27000, 'variants' => ['hot', 'ice']],
                            ['name' => 'Lychee Tea', 'description' => 'Teh leci segar.', 'price' => 22000, 'variants' => ['ice']],
                        ],
                    ],
                    [
                        'name' => 'Snack',
                        'items' => [
                            ['name' => 'French Fries', 'description' => 'Kentang goreng renyah.', 'price' => 22000],
                            ['name' => 'Banana Fritter', 'description' => 'Pisang goreng topping gula aren.', 'price' => 24000],
                            ['name' => 'Toast', 'description' => 'Roti panggang isi keju.', 'price' => 26000],
                        ],
                    ],
                ],
            ],
        };
    }

    private function tableCode(Cafe $cafe, int $number): string
    {
        $prefix = Str::upper(Str::slug($cafe->slug ?: $cafe->name, '-'));
        $prefix = Str::limit($prefix ?: 'CAFE', 14, '');

        return $prefix.'-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }
}
