<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('repeated menu visits retain only primitive cart session data', function () {
    $restaurant = Restaurant::factory()->create();
    $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
    ]);

    $this->post(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertRedirect();

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful();

    expect(session('cart_'.$restaurant->id))->toBe([
        $product->id => [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => [],
        ],
    ]);

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful();

    expect(session('cart_'.$restaurant->id))->toBe([
        $product->id => [
            'product_id' => $product->id,
            'quantity' => 2,
            'options' => [],
        ],
    ]);

    foreach (session()->all() as $value) {
        expect(containsEloquentObject($value))->toBeFalse();
    }
});

test('menu rendering stays below ten queries with a populated catalog', function () {
    Cache::flush();

    $restaurant = Restaurant::factory()->create();

    foreach (range(1, 4) as $index) {
        $category = Category::factory()->create([
            'restaurant_id' => $restaurant->id,
            'sort_order' => $index,
        ]);

        Product::factory()->count(4)->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
        ]);
    }

    DB::enableQueryLog();

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful();

    $firstVisitQueries = count(DB::getQueryLog());

    DB::flushQueryLog();

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful();

    $secondVisitQueries = count(DB::getQueryLog());

    DB::disableQueryLog();

    expect($firstVisitQueries)->toBeLessThan(10)
        ->and($secondVisitQueries)->toBeLessThan(10);
});

function containsEloquentObject(mixed $value): bool
{
    if ($value instanceof Model || $value instanceof EloquentCollection) {
        return true;
    }

    if (is_array($value)) {
        foreach ($value as $item) {
            if (containsEloquentObject($item)) {
                return true;
            }
        }
    }

    return false;
}
