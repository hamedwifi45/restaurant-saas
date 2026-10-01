<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['super_admin', 'restaurant_admin', 'kitchen_staff', 'driver'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        $theme = Theme::query()->updateOrCreate(
            ['slug' => 'burger-theme'],
            [
                'name' => 'برجر كلاسيك',
                'folder_name' => 'burger-theme',
                'is_default' => true,
                'is_active' => true,
                'version' => '1.0.0',
            ]
        );

        $starterRestaurant = Restaurant::query()->updateOrCreate(
            ['slug' => 'silver-chain'],
            [
                'name' => 'مطعم السلسلة الفضية',
                'subdomain' => 'silver-chain',
                'description' => 'وجبات يومية طازجة من مطبخ السلسلة الفضية.',
                'phone' => '0112200100',
                'email' => 'starter@restaurant.com',
                'address' => 'شارع الثورة، دمشق',
                'city' => 'دمشق',
                'delivery_fee' => 12000,
                'estimated_delivery_time' => 35,
                'theme_id' => $theme->id,
                'plan' => 'starter',
                'is_active' => true,
            ]
        );

        $proRestaurant = Restaurant::query()->updateOrCreate(
            ['slug' => 'qasr-al-sham'],
            [
                'name' => 'مطعم قصر الشام',
                'subdomain' => 'qasr-al-sham',
                'description' => 'مأكولات شامية ومشاوي على الطريقة الدمشقية.',
                'phone' => '0112200200',
                'email' => 'pro@restaurant.com',
                'address' => 'أبو رمانة، دمشق',
                'city' => 'دمشق',
                'delivery_fee' => 15000,
                'estimated_delivery_time' => 40,
                'theme_id' => $theme->id,
                'plan' => 'pro',
                'is_active' => true,
            ]
        );

        $this->upsertUser('superadmin@saas.com', 'مدير المنصة', 'super_admin');
        $this->upsertUser('starter@restaurant.com', 'مدير السلسلة الفضية', 'restaurant_admin', $starterRestaurant);
        $this->upsertUser('pro@restaurant.com', 'مدير قصر الشام', 'restaurant_admin', $proRestaurant);
        $this->upsertUser('kitchen@restaurant.com', 'موظف المطبخ', 'kitchen_staff', $proRestaurant);
        $driverUser = $this->upsertUser('driver@restaurant.com', 'سائق قصر الشام', 'driver', $proRestaurant);

        Driver::query()->updateOrCreate(
            ['user_id' => $driverUser->id],
            [
                'phone' => '0933000400',
                'vehicle_type' => 'دراجة نارية',
                'plate_number' => 'دمشق 4215',
                'rating' => 4.85,
            ]
        );

        $starterProducts = $this->seedCatalog($starterRestaurant, [
            [
                'slug' => 'silver-meals',
                'name' => 'وجبات السلسلة الفضية',
                'products' => [
                    ['slug' => 'silver-chicken-meal', 'name' => 'وجبة دجاج مشوي', 'price' => 42000],
                    ['slug' => 'silver-beef-burger', 'name' => 'برغر لحم كلاسيكي', 'price' => 38000],
                ],
            ],
            [
                'slug' => 'silver-sides',
                'name' => 'مقبلات ومشروبات',
                'products' => [
                    ['slug' => 'silver-fries', 'name' => 'بطاطا مقلية', 'price' => 14000],
                    ['slug' => 'silver-lemonade', 'name' => 'ليموناضة طازجة', 'price' => 10000],
                ],
            ],
        ]);

        $proProducts = $this->seedCatalog($proRestaurant, [
            [
                'slug' => 'shami-grills',
                'name' => 'مشاوي ومأكولات شامية',
                'products' => [
                    ['slug' => 'qasr-chicken-shawarma', 'name' => 'شاورما دجاج', 'price' => 35000],
                    ['slug' => 'qasr-beef-shawarma', 'name' => 'شاورما لحم', 'price' => 48000],
                    ['slug' => 'qasr-mixed-grill', 'name' => 'مشاوي مشكلة', 'price' => 72000],
                ],
            ],
            [
                'slug' => 'shami-dishes',
                'name' => 'أطباق ومقبلات شامية',
                'products' => [
                    ['slug' => 'qasr-hummus-fatteh', 'name' => 'فتة حمص', 'price' => 28000],
                    ['slug' => 'qasr-kibbeh', 'name' => 'كبة شامية', 'price' => 32000],
                    ['slug' => 'qasr-tabbouleh', 'name' => 'تبولة شامية', 'price' => 18000],
                ],
            ],
        ]);

        $table = RestaurantTable::query()->firstOrCreate(
            ['restaurant_id' => $proRestaurant->id, 'number' => 'DEMO-01'],
            [
                'name' => 'طاولة داخلية ١',
                'seats' => 4,
                'qr_token' => Str::random(24),
                'is_active' => true,
            ]
        );

        $this->seedOrders($starterRestaurant, $starterProducts, null, 4, 'DEMO-STARTER');
        $this->seedOrders($proRestaurant, $proProducts, $table, 120, 'DEMO-PRO');
        $this->seedExpenses($proRestaurant);
    }

    private function upsertUser(string $email, string $name, string $role, ?Restaurant $restaurant = null): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'restaurant_id' => $restaurant?->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * @param  array<int, array{slug: string, name: string, products: array<int, array{slug: string, name: string, price: int}>}>  $categories
     * @return Collection<int, Product>
     */
    private function seedCatalog(Restaurant $restaurant, array $categories): Collection
    {
        $products = collect();

        foreach ($categories as $categoryData) {
            $category = Category::query()->updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'description' => 'أطباق محضرة يوميًا بمكونات طازجة.',
                    'sort_order' => 1,
                    'is_active' => true,
                ]
            );

            foreach ($categoryData['products'] as $index => $productData) {
                $products->push(Product::query()->updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'slug' => $productData['slug']],
                    [
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'description' => 'يحضر طازجًا عند الطلب.',
                        'price' => $productData['price'],
                        'is_available' => true,
                        'is_featured' => $index === 0,
                        'sort_order' => $index + 1,
                    ]
                ));
            }
        }

        return $products->values();
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function seedOrders(Restaurant $restaurant, Collection $products, ?RestaurantTable $table, int $count, string $trackingPrefix): void
    {
        $statuses = ['delivered', 'delivered', 'ready', 'preparing', 'pending', 'delivered'];
        $customerNames = ['سارة الخطيب', 'عمر الحمصي', 'لينا العلي', 'كريم مراد', 'نور الشامي', 'رامي درويش'];
        $customerPhones = ['0930123456', '0944112233', '0955123400', '0966234511', '0987345622'];

        for ($index = 0; $index < $count; $index++) {
            $status = $statuses[$index % count($statuses)];
            $deliveryType = $table && $index % 3 === 0 ? 'dine_in' : 'delivery';
            $createdAt = now()->subDays(intdiv($index, 2))->setTime(10 + ($index % 10), ($index * 7) % 60);
            $itemCount = min($products->count(), 1 + ($index % 3));
            $items = [];
            $subtotal = 0.0;

            for ($itemIndex = 0; $itemIndex < $itemCount; $itemIndex++) {
                $product = $products[($index + $itemIndex) % $products->count()];
                $quantity = 1 + (($index + $itemIndex) % 3);
                $price = (float) $product->price;
                $lineTotal = round($price * $quantity, 2);
                $subtotal += $lineTotal;
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $taxAmount = round($subtotal * 0.15, 2);
            $deliveryFee = $deliveryType === 'delivery' ? (float) $restaurant->delivery_fee : 0.0;
            $offerDiscount = $index % 5 === 0 ? round($subtotal * 0.05, 2) : 0.0;
            $finalAmount = round($subtotal + $taxAmount + $deliveryFee - $offerDiscount, 2);
            $trackingCode = $trackingPrefix.'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $order = Order::query()->updateOrCreate(
                ['tracking_code' => $trackingCode],
                [
                    'restaurant_id' => $restaurant->id,
                    'restaurant_table_id' => $deliveryType === 'dine_in' ? $table?->id : null,
                    'customer_name' => $customerNames[$index % count($customerNames)],
                    'customer_phone' => $customerPhones[$index % count($customerPhones)],
                    'delivery_type' => $deliveryType,
                    'delivery_address' => $deliveryType === 'dine_in' ? 'داخل المطعم، طاولة '.$table?->number : 'دمشق، شارع بغداد',
                    'delivery_city' => $restaurant->city,
                    'table_number' => $deliveryType === 'dine_in' ? $table?->number : null,
                    'delivery_fee' => $deliveryFee,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount' => $offerDiscount,
                    'coupon_discount' => 0,
                    'total_amount' => $finalAmount,
                    'final_amount' => $finalAmount,
                    'payment_method' => $index % 2 === 0 ? 'cash' : 'transfer',
                    'payment_status' => in_array($status, ['delivered', 'ready'], true) ? 'verified' : 'pending',
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            foreach ($items as $item) {
                Order_item::query()->updateOrCreate(
                    ['order_id' => $order->id, 'product_id' => $item['product']->id],
                    [
                        'product_name' => $item['product']->name,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['total'],
                    ]
                );
            }

            if ($status === 'delivered') {
                Invoice::query()->updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'restaurant_id' => $restaurant->id,
                        'invoice_number' => $trackingPrefix.'-INV-'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT),
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'delivery_fee' => $deliveryFee,
                        'total_amount' => $finalAmount,
                        'final_amount' => $finalAmount,
                        'status' => 'paid',
                        'paid_at' => $createdAt,
                    ]
                );
            }
        }
    }

    private function seedExpenses(Restaurant $restaurant): void
    {
        $expenseTypes = [
            ['title' => 'رواتب الطهاة والموظفين', 'amount' => 1800000, 'variation' => 125000, 'category' => 'salaries'],
            ['title' => 'شراء لحوم وخضار طازجة', 'amount' => 2100000, 'variation' => 175000, 'category' => 'raw_materials'],
            ['title' => 'فاتورة الكهرباء والماء', 'amount' => 650000, 'variation' => 85000, 'category' => 'utilities'],
            ['title' => 'صيانة أجهزة المطبخ', 'amount' => 350000, 'variation' => 110000, 'category' => 'operational'],
        ];

        $expenseCount = 36;
        $today = now()->startOfDay();

        for ($index = 0; $index < $expenseCount; $index++) {
            $expense = $expenseTypes[$index % count($expenseTypes)];
            $spentAt = $today->subDays((int) floor($index * 60 / $expenseCount))->toDateString();

            Expense::query()->updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'title' => $expense['title'], 'spent_at' => $spentAt],
                [
                    'amount' => $expense['amount'] + (($index % 3) * $expense['variation']),
                    'category' => $expense['category'],
                    'notes' => 'مصروف تشغيلي تجريبي للتقارير المالية.',
                ]
            );
        }
    }
}
