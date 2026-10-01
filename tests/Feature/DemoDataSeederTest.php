<?php

use App\Models\Category;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

test('database seeding provisions roles and demo analytics idempotently', function () {
    Auth::logout();

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $superAdmin = User::query()->where('email', 'superadmin@saas.com')->sole();
    $starterAdmin = User::query()->where('email', 'starter@restaurant.com')->sole();
    $proAdmin = User::query()->where('email', 'pro@restaurant.com')->sole();
    $kitchenStaff = User::query()->where('email', 'kitchen@restaurant.com')->sole();
    $driver = User::query()->where('email', 'driver@restaurant.com')->sole();
    $starterRestaurant = Restaurant::query()->where('slug', 'silver-chain')->sole();
    $proRestaurant = Restaurant::query()->where('slug', 'qasr-al-sham')->sole();
    $proOrders = Order::query()
        ->where('restaurant_id', $proRestaurant->id)
        ->where('tracking_code', 'like', 'DEMO-PRO-%');
    $proStatuses = (clone $proOrders)->distinct()->pluck('status')->all();
    $deliveredProOrders = (clone $proOrders)->where('status', 'delivered');
    $oldestProOrder = CarbonImmutable::parse((clone $proOrders)->min('created_at'));
    $deliveredOrders = (clone $deliveredProOrders)->with('invoice')->get();
    $expenseCategories = Expense::query()->where('restaurant_id', $proRestaurant->id)->distinct()->pluck('category')->all();
    $oldestExpense = CarbonImmutable::parse(
        Expense::query()->where('restaurant_id', $proRestaurant->id)->min('spent_at')
    );

    expect($superAdmin->hasRole('super_admin'))->toBeTrue()
        ->and($starterAdmin->hasRole('restaurant_admin'))->toBeTrue()
        ->and($proAdmin->hasRole('restaurant_admin'))->toBeTrue()
        ->and($kitchenStaff->hasRole('kitchen_staff'))->toBeTrue()
        ->and($driver->hasRole('driver'))->toBeTrue()
        ->and($starterRestaurant->plan)->toBe('starter')
        ->and($proRestaurant->plan)->toBe('pro')
        ->and($proOrders->count())->toBe(120)
        ->and((clone $proOrders)->whereNotIn('payment_method', ['cash', 'transfer'])->count())->toBe(0)
        ->and((clone $proOrders)->where('delivery_type', 'dine_in')->count())->toBeGreaterThan(0)
        ->and((clone $proOrders)->where('delivery_type', 'delivery')->count())->toBeGreaterThan(0)
        ->and($proStatuses)->toContain('delivered')
        ->and($proStatuses)->toContain('ready')
        ->and($proStatuses)->toContain('preparing')
        ->and($proStatuses)->toContain('pending')
        ->and($oldestProOrder->lessThanOrEqualTo(now()->subDays(58)))->toBeTrue()
        ->and((clone $deliveredProOrders)->whereDoesntHave('invoice')->count())->toBe(0)
        ->and((clone $deliveredProOrders)->whereHas('invoice')->count())->toBe($deliveredProOrders->count())
        ->and($deliveredOrders->every(fn (Order $order): bool => $order->invoice->final_amount === $order->final_amount
            && $order->invoice->total_amount === $order->final_amount))->toBeTrue()
        ->and(Expense::query()->where('restaurant_id', $proRestaurant->id)->count())->toBeGreaterThanOrEqual(36)
        ->and($expenseCategories)->toContain('salaries', 'raw_materials', 'utilities', 'operational')
        ->and($oldestExpense->lessThanOrEqualTo(now()->subDays(58)))->toBeTrue()
        ->and(Driver::query()->where('user_id', $driver->id)->sole()->vehicle_type)->toBe('دراجة نارية')
        ->and(User::query()->where('email', 'admin@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'super@example.com')->exists())->toBeTrue();
});

test('restaurant menu cache is invalidated when catalog records change', function () {
    $this->seed(DemoDataSeeder::class);

    $restaurant = Restaurant::query()->where('slug', 'qasr-al-sham')->sole();
    $category = Category::query()->where('restaurant_id', $restaurant->id)->where('slug', 'shami-grills')->sole();
    $product = Product::query()->where('restaurant_id', $restaurant->id)->where('slug', 'qasr-chicken-shawarma')->sole();
    $menuUrl = route('restaurant.menu', ['slug' => $restaurant->slug]);

    $categories = $this->get($menuUrl)->assertSuccessful()->viewData('categories');
    expect($categories->flatMap(fn (Category $category): Collection => $category->products)->pluck('name'))
        ->toContain($product->name);

    $product->update(['name' => 'شاورما دجاج مميزة']);
    $categories = $this->get($menuUrl)->assertSuccessful()->viewData('categories');
    expect($categories->flatMap(fn (Category $category): Collection => $category->products)->pluck('name'))
        ->toContain('شاورما دجاج مميزة');

    $category->update(['name' => 'مشاوي قصر الشام']);
    $categories = $this->get($menuUrl)->assertSuccessful()->viewData('categories');
    expect($categories->pluck('name'))->toContain('مشاوي قصر الشام');

    $product->delete();
    $categories = $this->get($menuUrl)->assertSuccessful()->viewData('categories');
    expect($categories->flatMap(fn (Category $category): Collection => $category->products)->pluck('id'))
        ->not->toContain($product->id);

    $temporaryCategory = Category::query()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'تصنيف مؤقت',
        'slug' => 'temporary-category',
        'is_active' => true,
    ]);
    $categories = $this->get($menuUrl)->assertSuccessful()->viewData('categories');
    expect($categories->pluck('id'))->toContain($temporaryCategory->id);

    $temporaryCategory->delete();
    $categories = $this->get($menuUrl)->assertSuccessful()->viewData('categories');
    expect($categories->pluck('id'))->not->toContain($temporaryCategory->id);
});
