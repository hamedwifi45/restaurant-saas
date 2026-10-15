<?php

use App\Models\Category;
use App\Models\Image;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('oriental cart quantity and removal requests return asynchronous responses', function () {
    $restaurant = Restaurant::factory()->create();
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 125,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->postJson(route('cart.update', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 3,
    ])->assertSuccessful()
        ->assertJson([
            'success' => true,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 3],
        ],
    ])->postJson(route('cart.remove', $restaurant->slug), [
        'product_id' => $product->id,
    ])->assertSuccessful()
        ->assertJson([
            'success' => true,
            'product_id' => $product->id,
        ]);
});

test('oriental checkout renders delivery choices and Syrian phone validation', function () {
    $theme = Theme::factory()->create([
        'folder_name' => 'oriental-theme',
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'tax_percentage' => 7.5,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->get(route('checkout', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('value="delivery"', false)
        ->assertSee('value="dine_in"', false)
        ->assertSee('pattern="(09|\\+?9639)\\d{8}"', false)
        ->assertSee('7.50%')
        ->assertSee('7.50');
});

test('oriental checkout stores the configured restaurant tax rate', function () {
    $restaurant = Restaurant::factory()->create([
        'delivery_fee' => 0,
        'tax_percentage' => 5.5,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    Event::fake();

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->post(route('checkout.store', $restaurant->slug), [
        'name' => 'Customer',
        'phone' => '0930123456',
        'address' => 'Delivery address',
        'latitude' => 24.7,
        'longitude' => 46.7,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $order = Order::query()->sole();

    expect((float) $order->tax_amount)->toBe(5.5)
        ->and((float) $order->total_amount)->toBe(105.5);
});

test('cart summary includes the restaurant delivery fee in the final total', function (string $themeFolder) {
    $theme = Theme::factory()->create([
        'folder_name' => $themeFolder,
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme->id,
        'delivery_fee' => 17.5,
        'tax_percentage' => 10,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->get(route('cart.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('17.50')
        ->assertSee('127.50');
})->with([
    'Albasha Burger' => 'albasha-burger',
    'Burger Theme' => 'burger-theme',
    'Classic Burger' => 'classic-2-burger',
    'Oriental Theme' => 'oriental-theme',
]);

test('cart add response includes restaurant delivery fee in the grand total', function () {
    $restaurant = Restaurant::factory()->create([
        'delivery_fee' => 12.5,
        'tax_percentage' => 10,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);

    $this->postJson(route('cart.add', $restaurant->slug), [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful()
        ->assertJsonPath('subtotal', 100)
        ->assertJsonPath('delivery_fee', 12.5)
        ->assertJsonPath('grand_total', 122.5);
});

test('cart quantity updates accept one debounced batch and keep cart controls optimistic', function (string $themeFolder) {
    $theme = Theme::factory()->create([
        'folder_name' => $themeFolder,
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme?->id,
        'delivery_fee' => 12.5,
        'tax_percentage' => 10,
    ]);
    $firstProduct = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);
    $secondProduct = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 50,
    ]);
    $sessionCart = [
        $firstProduct->id => ['qty' => 1],
        $secondProduct->id => ['qty' => 2],
    ];

    $this->withSession(['cart_'.$restaurant->id => $sessionCart])
        ->postJson(route('cart.update', $restaurant->slug), [
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 3],
                ['product_id' => $secondProduct->id, 'quantity' => 4],
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('items.0.quantity', 3)
        ->assertJsonPath('items.1.quantity', 4);

    $this->assertSame(3, session('cart_'.$restaurant->id)[$firstProduct->id]['quantity']);
    $this->assertSame(4, session('cart_'.$restaurant->id)[$secondProduct->id]['quantity']);

    $this->withSession(['cart_'.$restaurant->id => [
        $firstProduct->id => ['qty' => 1],
        $secondProduct->id => ['qty' => 2],
    ]])->get(route('cart.index', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('cartQuantityManager', false)
        ->assertSee('changeQuantity('.$firstProduct->id.', -1)', false)
        ->assertSee('x-text="formatMoney(Number.isFinite(grandTotal) ? grandTotal :', false)
        ->assertSee('min-w-8 shrink-0 items-center justify-center text-center font-bold text-gray-800 tabular-nums', false)
        ->assertSee('x-text="quantities['.$firstProduct->id.'] ?? 1"', false)
        ->assertSee('200.00')
        ->assertSee('20.00')
        ->assertSee('232.50');
})->with([
    'Albasha Burger' => 'albasha-burger',
    'Albasha Burger Example' => 'albasha-burger-example',
    'Unavailable theme folder fallback' => 'burger-theme',
]);

test('menu and home product cards link to detail pages which render product reviews', function (string $themeFolder) {
    $theme = Theme::factory()->create([
        'folder_name' => $themeFolder,
    ]);
    $restaurant = Restaurant::factory()->create([
        'theme_id' => $theme?->id,
    ]);
    $category = Category::factory()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Burgers',
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'name' => 'House Burger',
    ]);
    $relatedProduct = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'category_id' => $category->id,
        'name' => 'Classic Burger',
    ]);
    Image::query()->create([
        'imageable_type' => Product::class,
        'imageable_id' => $product->id,
        'path' => 'products/house-burger-main.jpg',
        'alt' => 'House Burger main image',
        'sort_order' => 0,
        'is_primary' => true,
    ]);
    Image::query()->create([
        'imageable_type' => Product::class,
        'imageable_id' => $product->id,
        'path' => 'products/house-burger-detail.jpg',
        'alt' => 'House Burger detail image',
        'sort_order' => 1,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
    ]);
    Order_item::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
    ]);
    Review::factory()->create([
        'order_id' => $order->id,
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'Happy Customer',
        'rating' => 5,
        'comment' => 'Fresh and delicious.',
    ]);

    $detailUrl = route('product.show', [$restaurant->slug, $product->id]);

    $this->get(route('restaurant.menu', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('href="'.$detailUrl.'"', false);

    $this->get(route('restaurant.home', $restaurant->slug))
        ->assertSuccessful()
        ->assertSee('href="'.$detailUrl.'"', false);

    $this->get($detailUrl)
        ->assertSuccessful()
        ->assertSee('تقييمات هذا المنتج')
        ->assertSee('name="quantity"', false)
        ->assertSee('min="1"', false)
        ->assertSee('name="rating" :value="rating"', false)
        ->assertSee('x-data="{ rating: 0, hoveredRating: 0 }"', false)
        ->assertSee('@click="rating = 5"', false)
        ->assertSee(':class="(hoveredRating || rating) >= 5 ? \'text-yellow-400\' : \'text-gray-300\'"', false)
        ->assertSee('House Burger main image')
        ->assertSee('House Burger detail image')
        ->assertSee('منتجات مشابهة قد تعجبك')
        ->assertSee($relatedProduct->name)
        ->assertSee('bg-black/50', false)
        ->assertSee('role="dialog" aria-modal="true"', false)
        ->assertSee('id="review-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" aria-hidden="true"', false)
        ->assertSee('Fresh and delicious.')
        ->assertSee('1 تقييم')
        ->assertSee('5.0');
})->with([
    'Albasha Burger' => 'albasha-burger',
    'Albasha Burger Example' => 'albasha-burger-example',
    'Unavailable theme folder fallback' => 'burger-theme',
]);
