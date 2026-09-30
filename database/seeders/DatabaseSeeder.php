<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
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

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
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

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'مدير المطعم',
                'role' => 'restaurant_admin',
                'restaurant_id' => $restaurant->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
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

        User::query()->updateOrCreate(
            ['email' => 'super@example.com'],
            [
                'name' => 'مدير النظام',
                'role' => 'super_admin',
                'restaurant_id' => null,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'restaurant_admin']);
        Role::firstOrCreate(['name' => 'kitchen_staff']);
        Role::firstOrCreate(['name' => 'driver']);

        $this->call(DeliveryTrackingSeeder::class);

        $this->command?->newLine();
        $this->command?->info('بيانات الدخول العامة للاختبار');
        $this->command?->line('مدير المطعم');
        $this->command?->line('  Email: admin@example.com');
        $this->command?->line('  Password: password');
        $this->command?->line('مدير النظام');
        $this->command?->line('  Email: super@example.com');
        $this->command?->line('  Password: password');
        $this->command?->line('للدخول إلى لوحة المندوب استخدم: /driver/orders');
    }
}
