<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\CafeTable;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultCafe = Cafe::firstOrCreate(
            ['slug' => 'payment-cafe'],
            [
                'name' => config('app.name', 'Payment Cafe'),
                'status' => 'active',
                'active_from' => now()->toDateString(),
            ]
        );

        $developerEmail = env('DEVELOPER_EMAIL');
        $developerPassword = env('DEVELOPER_PASSWORD');

        $users = collect([
            ['name' => 'Admin Cafe', 'email' => 'admin@payment-cafe.test', 'role' => 'admin'],
            ['name' => 'Kasir Cafe', 'email' => 'kasir@payment-cafe.test', 'role' => 'cashier'],
            ['name' => 'Dapur Cafe', 'email' => 'dapur@payment-cafe.test', 'role' => 'kitchen'],
        ]);

        if ($developerEmail && $developerPassword) {
            $users->prepend([
                'name' => env('DEVELOPER_NAME', 'Developer Payment Cafe'),
                'email' => $developerEmail,
                'role' => 'developer',
                'password' => $developerPassword,
            ]);
        }

        $users->each(function (array $user) use ($defaultCafe) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'cafe_id' => in_array($user['role'], ['admin', 'cashier', 'kitchen'], true) ? $defaultCafe->id : null,
                    'is_active' => true,
                    'password' => Hash::make($user['password'] ?? 'password'),
                ]
            );
        });

        collect(range(1, 8))->each(function (int $number) use ($defaultCafe) {
            $code = 'MEJA-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            CafeTable::updateOrCreate(
                ['code' => $code],
                [
                    'cafe_id' => $defaultCafe->id,
                    'name' => 'Meja '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                    'capacity' => $number <= 4 ? 2 : 4,
                    'is_active' => true,
                ]
            );
        });

        $categories = [
            'Coffee' => [
                ['Americano', 'Espresso dan air panas dengan rasa bersih.', 18000, 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=600&q=80'],
                ['Cafe Latte', 'Espresso, susu steamed, dan foam tipis.', 26000, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80'],
                ['Caramel Macchiato', 'Kopi susu manis dengan caramel.', 32000, 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=600&q=80'],
            ],
            'Non Coffee' => [
                ['Matcha Latte', 'Matcha creamy dengan susu segar.', 28000, 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=600&q=80'],
                ['Chocolate Signature', 'Cokelat pekat dengan susu.', 27000, 'https://images.unsplash.com/photo-1542990253-0d0f5be5f0ed?auto=format&fit=crop&w=600&q=80'],
            ],
            'Food' => [
                ['Chicken Rice Bowl', 'Nasi, ayam saus gurih, dan salad.', 38000, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=600&q=80'],
                ['Beef Toast', 'Roti panggang isi beef dan keju.', 34000, 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=600&q=80'],
            ],
            'Snack' => [
                ['French Fries', 'Kentang goreng renyah dengan saus.', 22000, 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=600&q=80'],
                ['Banana Fritter', 'Pisang goreng dengan topping gula aren.', 24000, 'https://images.unsplash.com/photo-1603052875302-d376b7c0638a?auto=format&fit=crop&w=600&q=80'],
            ],
        ];

        $sort = 10;

        foreach ($categories as $categoryName => $items) {
            $category = MenuCategory::updateOrCreate(
                [
                    'cafe_id' => $defaultCafe->id,
                    'name' => $categoryName,
                ],
                ['sort_order' => $sort]
            );

            foreach ($items as $index => [$name, $description, $price, $imageUrl]) {
                MenuItem::updateOrCreate(
                    [
                        'menu_category_id' => $category->id,
                        'name' => $name,
                    ],
                    [
                        'description' => $description,
                        'price' => $price,
                        'image_url' => $imageUrl,
                        'variants' => in_array($categoryName, ['Coffee', 'Non Coffee'], true) ? ['hot', 'ice'] : [],
                        'is_available' => true,
                        'sort_order' => ($index + 1) * 10,
                    ]
                );
            }

            $sort += 10;
        }
    }
}
