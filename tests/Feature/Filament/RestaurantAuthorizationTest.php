<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Theme;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RestaurantAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_admin_cannot_access_records_from_another_restaurant(): void
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

        $categoryB = Category::create([
            'restaurant_id' => $restaurantB->id,
            'name' => 'Cat B',
            'slug' => 'cat-b',
            'is_active' => true,
        ]);

        $productB = Product::create([
            'restaurant_id' => $restaurantB->id,
            'category_id' => $categoryB->id,
            'name' => 'Product B',
            'slug' => 'product-b',
            'price' => 20,
            'old_price' => 25,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => 2,
        ]);

        $this->assertTrue(Gate::forUser($user)->check('view', $categoryA));
        $this->assertFalse(Gate::forUser($user)->check('view', $categoryB));
        $this->assertFalse(Gate::forUser($user)->check('update', $categoryB));
        $this->assertFalse(Gate::forUser($user)->check('view', $productB));
    }

    public function test_restaurant_forms_do_not_expose_the_restaurant_id_field(): void
    {
        $schema = CategoryResource::form(Schema::make());
        $componentNames = collect($schema->getComponents())
            ->map(fn ($component) => $component->getName())
            ->filter()
            ->all();

        $this->assertNotContains('restaurant_id', $componentNames);

        $productSchema = ProductResource::form(Schema::make());
        $productComponentNames = collect($productSchema->getComponents())
            ->map(fn ($component) => $component->getName())
            ->filter()
            ->all();

        $this->assertNotContains('restaurant_id', $productComponentNames);
    }
}
