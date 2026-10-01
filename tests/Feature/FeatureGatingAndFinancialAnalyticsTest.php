<?php

use App\Filament\Restaurant\Pages\FinancialAnalytics;
use App\Filament\Restaurant\Resources\Drivers\DriverResource;
use App\Filament\Restaurant\Resources\Expenses\ExpenseResource;
use App\Filament\Restaurant\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Restaurant\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Restaurant\Resources\Invoices\InvoiceResource;
use App\Filament\Restaurant\Resources\RestaurantTables\RestaurantTableResource;
use App\Filament\Restaurant\Widgets\FinancialOverviewWidget;
use App\Filament\Restaurant\Widgets\TopProductsWidget;
use App\Models\Coupon;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('checkout persists consistent order and invoice financial amounts', function () {
    $restaurant = Restaurant::factory()->create([
        'delivery_fee' => 10,
        'tax_percentage' => 15,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => 100,
    ]);
    $coupon = Coupon::factory()->create([
        'restaurant_id' => $restaurant->id,
        'value' => 10,
    ]);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 2],
        ],
    ])->post(route('checkout.store', $restaurant->slug), [
        'name' => 'Customer',
        'phone' => '0930123456',
        'address' => 'Restaurant delivery address',
        'latitude' => 24.7,
        'longitude' => 46.7,
        'payment_method' => 'cash',
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
    ])->assertRedirect();

    $order = Order::query()->sole();
    $invoice = Invoice::query()->sole();

    expect((float) $order->subtotal)->toBe(200.0)
        ->and((float) $order->tax_amount)->toBe(30.0)
        ->and((float) $order->delivery_fee)->toBe(10.0)
        ->and((float) $order->coupon_discount)->toBe(20.0)
        ->and((float) $order->total_amount)->toBe(220.0)
        ->and((float) $order->final_amount)->toBe(220.0)
        ->and((float) $invoice->subtotal)->toBe(200.0)
        ->and((float) $invoice->tax_amount)->toBe(30.0)
        ->and((float) $invoice->delivery_fee)->toBe(10.0)
        ->and((float) $invoice->final_amount)->toBe(220.0)
        ->and((float) $invoice->total_amount)->toBe(220.0);
});

test('feature access requires the appropriate plan and role', function () {
    $starterRestaurant = Restaurant::factory()->create(['plan' => 'starter']);
    $proRestaurant = Restaurant::factory()->create(['plan' => 'pro']);

    $starterAdmin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $starterRestaurant->id,
    ]);
    $proAdmin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $proRestaurant->id,
    ]);
    $kitchenStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $proRestaurant->id,
    ]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $starterRestaurant->id,
    ]);

    expect($starterRestaurant->hasFeature('table_qr'))->toBeTrue()
        ->and($starterRestaurant->hasFeature('kds'))->toBeFalse()
        ->and(Gate::forUser($starterAdmin)->allows('access-kds'))->toBeFalse()
        ->and(Gate::forUser($proAdmin)->allows('access-kds'))->toBeTrue()
        ->and(Gate::forUser($kitchenStaff)->allows('access-kds'))->toBeTrue()
        ->and(Gate::forUser($kitchenStaff)->allows('access-theme-editor'))->toBeFalse()
        ->and(Gate::forUser($proAdmin)->allows('access-theme-editor'))->toBeTrue()
        ->and(Gate::forUser($proAdmin)->allows('advanced-financial-analytics'))->toBeTrue()
        ->and(Gate::forUser($driver)->allows('access-driver-panel'))->toBeTrue();

    $this->actingAs($starterAdmin);
    expect(FinancialOverviewWidget::canView())->toBeFalse();

    $this->actingAs($proAdmin);
    expect(FinancialOverviewWidget::canView())->toBeTrue();
});

test('financial overview calculates gross revenue expenses discounts and net profit', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 200,
        'tax_amount' => 30,
        'delivery_fee' => 10,
        'discount' => 10,
        'coupon_discount' => 20,
        'total_amount' => 210,
        'final_amount' => 210,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'Supplies',
        'amount' => 40,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);

    $expense = Expense::query()->sole();

    expect($expense->spent_at->toDateString())->toBe(now()->toDateString())
        ->and((float) Expense::query()->where('restaurant_id', $restaurant->id)->sum('amount'))->toBe(40.0)
        ->and((float) Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('spent_at', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('spent_at', '<=', now()->endOfMonth()->toDateString())
            ->sum('amount'))->toBe(40.0);

    $this->actingAs($admin);

    Livewire::test(FinancialOverviewWidget::class)
        ->assertSee('إجمالي المبيعات')
        ->assertSee('صافي الأرباح')
        ->assertSee('المصاريف')
        ->assertSee('متوسط الطلب')
        ->assertSee('240.00 ل.س')
        ->assertSee('40.00 ل.س')
        ->assertSee('210.00 ل.س')
        ->assertSee('170.00 ل.س');
});

test('financial analytics page is restricted to pro restaurant admins', function () {
    $starterRestaurant = Restaurant::factory()->create(['plan' => 'starter']);
    $proRestaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $starterAdmin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $starterRestaurant->id,
    ]);
    $proAdmin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $proRestaurant->id,
    ]);

    $this->actingAs($starterAdmin)
        ->get('/restaurant/financial-analytics')
        ->assertForbidden();

    expect(FinancialAnalytics::canAccess())->toBeFalse();

    $this->actingAs($proAdmin)
        ->get('/restaurant/financial-analytics')
        ->assertOk()
        ->assertSee('التقارير والتحليلات المالية')
        ->assertSee('FinancialOverviewWidget')
        ->assertSee('TopProductsWidget')
        ->assertSee('OrderTypeChartWidget')
        ->assertSee('SalesChartWidget')
        ->assertSee('PaymentMethodChartWidget');
});

test('top products table shows verified product quantity and revenue', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    Order_item::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'طبق تجريبي',
        'quantity' => 4,
        'price' => 25,
        'total' => 100,
    ]);

    $this->actingAs($admin);

    Livewire::test(TopProductsWidget::class)
        ->assertSee('طبق تجريبي')
        ->assertSee('4')
        ->assertSee('100.00 ل.س');
});

test('KDS actions reject restaurants other than the authenticated user restaurant', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $otherRestaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $foreignOrder = Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
    ]);

    $this->actingAs($admin)
        ->get(route('kitchen.display', $otherRestaurant->slug))
        ->assertForbidden();

    $this->post(route('kitchen.orders.updateStatus', $foreignOrder->tracking_code), [
        'status' => 'preparing',
    ])->assertForbidden();
});

test('restaurant table driver and invoice resources remain tenant scoped', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $table = RestaurantTable::factory()->create(['restaurant_id' => $restaurant->id]);
    RestaurantTable::factory()->create(['restaurant_id' => $otherRestaurant->id]);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $otherRestaurant->id,
    ]);
    $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);
    $invoice = Invoice::factory()->create([
        'order_id' => $order->id,
        'restaurant_id' => $restaurant->id,
    ]);
    $otherOrder = Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);
    Invoice::factory()->create([
        'order_id' => $otherOrder->id,
        'restaurant_id' => $otherRestaurant->id,
    ]);

    $this->actingAs($admin);

    expect(RestaurantTableResource::getEloquentQuery()->pluck('id')->all())->toBe([$table->id])
        ->and(DriverResource::getEloquentQuery()->pluck('id')->all())->toBe([$driver->id])
        ->and(InvoiceResource::getEloquentQuery()->pluck('id')->all())->toBe([$invoice->id]);
});

test('expense resource enforces restaurant isolation and assigns the creating tenant', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $ownExpense = Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'رواتب شهرية',
        'amount' => 250000,
        'category' => 'salaries',
        'spent_at' => now()->toDateString(),
    ]);
    $foreignExpense = Expense::create([
        'restaurant_id' => $otherRestaurant->id,
        'title' => 'مصروف مطعم آخر',
        'amount' => 100000,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);

    $this->actingAs($admin);

    expect(ExpenseResource::canViewAny())->toBeTrue()
        ->and(ExpenseResource::getEloquentQuery()->pluck('id')->all())->toBe([$ownExpense->id])
        ->and(ExpenseResource::canEdit($ownExpense))->toBeTrue()
        ->and(ExpenseResource::canEdit($foreignExpense))->toBeFalse();

    Filament::setCurrentPanel('restaurant');

    try {
        Livewire::test(CreateExpense::class)
            ->fillForm([
                'title' => 'شراء مواد أولية',
                'amount' => 125000,
                'category' => 'raw_materials',
                'spent_at' => now()->toDateString(),
                'notes' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(ListExpenses::class)
            ->assertSee('رواتب شهرية')
            ->assertSee('250,000.00 ل.س')
            ->assertSee('مواد أولية ومشتريات');
    } finally {
        Filament::setCurrentPanel('sham');
    }

    expect(Expense::query()->where('title', 'شراء مواد أولية')->sole()->restaurant_id)
        ->toBe($restaurant->id);

    $kitchenStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($kitchenStaff);

    expect(ExpenseResource::canViewAny())->toBeFalse()
        ->and(ExpenseResource::canCreate())->toBeFalse();
});
