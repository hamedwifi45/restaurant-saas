<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantThemeSetting;
use App\Models\Review;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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

        $restaurant = Restaurant::query()->updateOrCreate(
            ['slug' => 'burger-house'],
            [
                'name' => 'برجر هاوس',
                'subdomain' => 'burger-house',
                'theme_id' => $theme->id,
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'مدير المطعم',
                'role' => 'restaurant_admin',
                'restaurant_id' => $restaurant->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['restaurant_admin']);

        $customer = User::query()->updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'عميل تجريبي',
                'role' => 'customer',
                'restaurant_id' => null,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        RestaurantThemeSetting::query()->updateOrCreate(
            ['restaurant_id' => $restaurant->id],
            ['theme_id' => $theme->id]
        );

        $categories = Category::factory(2)->create(['restaurant_id' => $restaurant->id]);
        $products = Product::factory(3)->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $categories->first()->id,
        ]);

        $offer = Offer::factory()->create(['restaurant_id' => $restaurant->id]);
        $offer->products()->attach($products->take(2)->modelKeys());
        $coupon = Coupon::query()->updateOrCreate(
            ['code' => 'WELCOME20'],
            ['restaurant_id' => $restaurant->id]
        );

        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => '0500000002',
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'status' => 'delivered',
            'payment_status' => 'verified',
        ]);

        foreach ($products->take(2) as $product) {
            Order_item::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'total' => $product->price,
            ]);
        }

        Invoice::factory()->create([
            'order_id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        Review::factory()->create([
            'order_id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'customer_name' => $customer->name,
            'rating' => 5,
        ]);

        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'super@example.com'],
            [
                'name' => 'مدير النظام',
                'role' => 'super_admin',
                'restaurant_id' => null,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        $this->call(DemoDataSeeder::class);
        $this->call(DeliveryTrackingSeeder::class);

        $this->command?->newLine();
        $this->command?->table(
            ['Role', 'Plan', 'Email', 'Password', 'Description / Access Scope'],
            [
                ['super_admin', 'all plans', 'superadmin@saas.com', 'password', 'Platform-wide access'],
                ['restaurant_admin', 'starter', 'starter@restaurant.com', 'password', 'مطعم السلسلة الفضية'],
                ['restaurant_admin', 'pro', 'pro@restaurant.com', 'password', 'مطعم قصر الشام'],
                ['kitchen_staff', 'pro', 'kitchen@restaurant.com', 'password', 'KDS access for مطعم قصر الشام'],
                ['driver', 'pro', 'driver@restaurant.com', 'password', 'Driver panel for مطعم قصر الشام'],
            ]
        );
    }
}
