<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantScopedResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scopes_categories_products_and_orders_to_the_authenticated_restaurant(): void
    {
        $theme = Theme::create([
            'name' => 'Theme A',
            'slug' => 'theme-a',
            'is_active' => true,
        ]);

        $restaurantA = Restaurant::create([
            'name' => 'Restaurant A',
            'slug' => 'restaurant-a',
            'subdomain' => 'a',
            'theme_id' => $theme->id,
            'is_active' => true,
        ]);

        $restaurantB = Restaurant::create([
            'name' => 'Restaurant B',
            'slug' => 'restaurant-b',
            'subdomain' => 'b',
            'theme_id' => $theme->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'restaurant_id' => $restaurantA->id,
        ]);

        $categoryA = Category::create([
            'restaurant_id' => $restaurantA->id,
            'name' => 'Cat A',
            'slug' => 'cat-a',
            'is_active' => true,
        ]);

        Category::create([
            'restaurant_id' => $restaurantB->id,
            'name' => 'Cat B',
            'slug' => 'cat-b',
            'is_active' => true,
        ]);

        $productA = Product::create([
            'restaurant_id' => $restaurantA->id,
            'category_id' => $categoryA->id,
            'name' => 'Product A',
            'slug' => 'product-a',
            'price' => 10,
            'old_price' => 12,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ]);

        Product::create([
            'restaurant_id' => $restaurantB->id,
            'category_id' => $categoryA->id,
            'name' => 'Product B',
            'slug' => 'product-b',
            'price' => 20,
            'old_price' => 25,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 2,
        ]);

        $orderA = Order::create([
            'restaurant_id' => $restaurantA->id,
            'customer_name' => 'Alice',
            'customer_phone' => '123456789',
            'subtotal' => 10,
            'total_amount' => 10,
        ]);

        Order::create([
            'restaurant_id' => $restaurantB->id,
            'customer_name' => 'Bob',
            'customer_phone' => '987654321',
            'subtotal' => 20,
            'total_amount' => 20,
        ]);

        $this->actingAs($user);

        $this->assertTrue(CategoryResource::getEloquentQuery()->pluck('id')->contains($categoryA->id));
        $this->assertTrue(ProductResource::getEloquentQuery()->pluck('id')->contains($productA->id));
        $this->assertTrue(OrderResource::getEloquentQuery()->pluck('id')->contains($orderA->id));
    }
}
