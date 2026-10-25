<?php

use App\Filament\Restaurant\Pages\FinancialAnalytics;
use App\Filament\Restaurant\Resources\Drivers\DriverResource;
use App\Filament\Restaurant\Resources\Expenses\ExpenseResource;
use App\Filament\Restaurant\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Restaurant\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Restaurant\Resources\Invoices\InvoiceResource;
use App\Filament\Restaurant\Resources\Orders\Pages\ListOrders;
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
use App\Services\FinancialAnalyticsService;
use App\Services\RestaurantDashboardService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

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
        ->and(Gate::forUser($driver)->allows('access-driver-panel', $starterRestaurant->slug))->toBeTrue()
        ->and(Gate::forUser($driver)->allows('access-driver-panel', $proRestaurant->slug))->toBeFalse();

    $this->actingAs($starterAdmin);
    expect(FinancialOverviewWidget::canView())->toBeFalse();

    $this->actingAs($proAdmin);
    expect(FinancialOverviewWidget::canView())->toBeTrue();
});

test('driver panel permission is scoped to the user restaurant', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $user = User::factory()->create([
        'role' => 'customer',
        'restaurant_id' => $restaurant->id,
    ]);
    $user->givePermissionTo(Permission::findOrCreate('access-driver-panel', 'web'));

    expect(Gate::forUser($user)->allows('access-driver-panel', $restaurant->slug))->toBeTrue()
        ->and(Gate::forUser($user)->allows('access-driver-panel', $otherRestaurant->slug))->toBeFalse();

    $this->actingAs($user)
        ->get(route('driver.orders', $restaurant->slug))
        ->assertSuccessful();
});

test('financial overview distinguishes product sales discounts expenses and average order value', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $otherRestaurant = Restaurant::factory()->create(['plan' => 'pro']);
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
        'discount_amount' => 10,
        'coupon_discount' => 20,
        'total_amount' => 210,
        'final_amount' => 210,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 500,
        'payment_status' => 'pending',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 300,
        'payment_status' => 'verified',
        'status' => 'cancelled',
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'subtotal' => 9000,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    $deletedOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 4000,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    $deletedOrder->forceFill(['deleted_at' => now()])->save();
    Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'Supplies',
        'amount' => 40,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);
    Expense::create([
        'restaurant_id' => $otherRestaurant->id,
        'title' => 'Other restaurant supplies',
        'amount' => 900,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);

    $expense = Expense::query()->where('restaurant_id', $restaurant->id)->sole();

    expect($expense->spent_at->toDateString())->toBe(now()->toDateString())
        ->and((float) Expense::query()->where('restaurant_id', $restaurant->id)->sum('amount'))->toBe(40.0)
        ->and((float) Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('spent_at', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('spent_at', '<=', now()->endOfMonth()->toDateString())
            ->sum('amount'))->toBe(40.0);

    $this->actingAs($admin);

    Livewire::test(FinancialOverviewWidget::class)
        ->assertSee('إجمالي مبيعات الطلبات')
        ->assertSee('الخصومات')
        ->assertSee('صافي المبيعات')
        ->assertSee('المصاريف المسجلة')
        ->assertSee('متوسط قيمة الطلب المؤكد')
        ->assertDontSee('صافي الأرباح')
        ->assertSee('200.00 ل.س')
        ->assertSee('30.00 ل.س')
        ->assertSee('170.00 ل.س')
        ->assertSee('40.00 ل.س')
        ->assertSee('210.00 ل.س')
        ->assertDontSee('9,000.00 ل.س')
        ->assertDontSee('900.00 ل.س');
});

test('financial metrics use a selected period and compare it with the preceding equivalent period', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $today = now()->startOfDay();
    $analytics = app(FinancialAnalyticsService::class);
    $selectedPeriod = $analytics->period([
        'startDate' => $today->copy()->subDays(2)->toDateString(),
        'endDate' => $today->toDateString(),
    ]);

    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 200,
        'discount' => 20,
        'coupon_discount' => 10,
        'final_amount' => 180,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $today->copy()->subDay(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 100,
        'discount' => 0,
        'coupon_discount' => 0,
        'final_amount' => 100,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $today->copy()->subDays(4),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 500,
        'final_amount' => 500,
        'payment_status' => 'pending',
        'status' => 'delivered',
        'created_at' => $today,
    ]);
    $cancelledOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'subtotal' => 90,
        'payment_status' => 'verified',
        'status' => 'cancelled',
        'created_at' => $today,
    ]);
    Invoice::factory()->create([
        'order_id' => $cancelledOrder->id,
        'restaurant_id' => $restaurant->id,
        'status' => 'refunded',
    ]);
    Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'subtotal' => 9000,
        'final_amount' => 9000,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $today,
    ]);
    Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'Period expense',
        'amount' => 35,
        'category' => 'operational',
        'spent_at' => $today->toDateString(),
    ]);
    Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'Previous period expense',
        'amount' => 10,
        'category' => 'operational',
        'spent_at' => $today->copy()->subDays(4)->toDateString(),
    ]);
    Expense::create([
        'restaurant_id' => $otherRestaurant->id,
        'title' => 'Other restaurant expense',
        'amount' => 900,
        'category' => 'operational',
        'spent_at' => $today->toDateString(),
    ]);

    $current = $analytics->metrics($restaurant, $selectedPeriod);
    $previous = $analytics->metrics($restaurant, [
        ...$selectedPeriod,
        'start' => $selectedPeriod['previous_start'],
        'end' => $selectedPeriod['previous_end'],
    ]);

    expect($current['gross_sales'])->toBe(200.0)
        ->and($current['discounts'])->toBe(30.0)
        ->and($current['net_sales'])->toBe(170.0)
        ->and($current['paid_order_count'])->toBe(1)
        ->and($current['average_paid_order_value'])->toBe(180.0)
        ->and($current['order_count'])->toBe(3)
        ->and($current['cancelled_order_count'])->toBe(1)
        ->and($current['cancellation_rate'])->toBe(1 / 3)
        ->and($current['refunded_invoice_order_count'])->toBe(1)
        ->and($current['recorded_expenses'])->toBe(35.0)
        ->and($previous['gross_sales'])->toBe(100.0)
        ->and($previous['recorded_expenses'])->toBe(10.0);
});

test('financial reporting safely handles empty and invalid date periods', function () {
    $restaurant = Restaurant::factory()->create();
    $analytics = app(FinancialAnalyticsService::class);
    $period = $analytics->period([
        'startDate' => '2026-02-31',
        'endDate' => 'not-a-date',
    ]);
    $metrics = $analytics->metrics($restaurant, $period);

    expect($metrics['gross_sales'])->toBe(0.0)
        ->and($metrics['discounts'])->toBe(0.0)
        ->and($metrics['net_sales'])->toBe(0.0)
        ->and($metrics['paid_order_count'])->toBe(0)
        ->and($metrics['average_paid_order_value'])->toBe(0.0)
        ->and($metrics['cancellation_rate'])->toBe(0.0)
        ->and($metrics['recorded_expenses'])->toBe(0.0)
        ->and($period['days'])->toBeGreaterThan(0);
});

test('order demand patterns count all non-cancelled orders for the tenant', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();
    $weekStart = today()->startOfWeek();
    $period = app(FinancialAnalyticsService::class)->period([
        'startDate' => $weekStart->toDateString(),
        'endDate' => $weekStart->copy()->addDays(6)->toDateString(),
    ]);

    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'payment_status' => 'pending',
        'status' => 'pending',
        'created_at' => $weekStart->copy()->setTime(14, 0),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $weekStart->copy()->addDays(3)->setTime(9, 0),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'payment_status' => 'verified',
        'status' => 'cancelled',
        'created_at' => $weekStart->copy()->setTime(14, 0),
    ]);
    Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $weekStart->copy()->setTime(14, 0),
    ]);

    $analytics = app(FinancialAnalyticsService::class);

    expect($analytics->orderCountsByPattern($restaurant, $period, 'hour'))
        ->toBe([9 => 1, 14 => 1])
        ->and($analytics->orderCountsByPattern($restaurant, $period, 'weekday'))
        ->toBe([0 => 1, 3 => 1]);
});

test('restaurant dashboard sales include only verified non-cancelled order totals', function () {
    $restaurant = Restaurant::factory()->create();
    $otherRestaurant = Restaurant::factory()->create();

    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_phone' => 'verified-customer',
        'total_amount' => 185,
        'final_amount' => 150,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_phone' => 'unpaid-customer',
        'total_amount' => 400,
        'final_amount' => 400,
        'payment_status' => 'pending',
        'status' => 'pending',
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_phone' => 'cancelled-customer',
        'total_amount' => 300,
        'final_amount' => 300,
        'payment_status' => 'verified',
        'status' => 'cancelled',
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_phone' => 'deposit-customer',
        'deposit_amount' => 25,
        'total_amount' => 250,
        'final_amount' => 250,
        'payment_status' => 'deposit_verified',
        'status' => 'pending',
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'total_amount' => 9000,
        'final_amount' => 9000,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    $deletedOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_phone' => 'deleted-customer',
        'total_amount' => 800,
        'final_amount' => 800,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    $deletedOrder->forceFill(['deleted_at' => now()])->save();

    $summary = app(RestaurantDashboardService::class)
        ->forRestaurant($restaurant)['summary'];

    expect($summary['sales_today'])->toBe(150.0)
        ->and($summary['average_order'])->toBe(150.0)
        ->and($summary['customers_today'])->toBe(3);
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
        ->assertSee('من تاريخ')
        ->assertSee('حسب الساعة')
        ->assertSee('حسب يوم الأسبوع')
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

test('financial report drilldowns apply date and status filters without crossing restaurant tenants', function () {
    $restaurant = Restaurant::factory()->create(['currency_symbol' => 'USD']);
    $otherRestaurant = Restaurant::factory()->create();
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);
    $startDate = today()->subDays(2);
    $endDate = today();

    $verifiedOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'Included verified order',
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $startDate->copy()->setTime(12, 0),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'Excluded unpaid order',
        'payment_status' => 'pending',
        'status' => 'pending',
        'created_at' => $startDate->copy()->setTime(13, 0),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'Included cancelled order',
        'payment_status' => 'verified',
        'status' => 'cancelled',
        'created_at' => $startDate->copy()->setTime(14, 0),
    ]);
    $refundedOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'Included refunded invoice order',
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $startDate->copy()->setTime(15, 0),
    ]);
    Invoice::factory()->create([
        'order_id' => $refundedOrder->id,
        'restaurant_id' => $restaurant->id,
        'status' => 'refunded',
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'Excluded outside-period order',
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $startDate->copy()->subDay(),
    ]);
    $deletedOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'customer_name' => 'Excluded deleted order',
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $startDate->copy()->setTime(16, 0),
    ]);
    $deletedOrder->forceFill(['deleted_at' => now()])->save();
    Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'customer_name' => 'Excluded foreign order',
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => $startDate->copy()->setTime(14, 0),
    ]);
    Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'Included period expense',
        'amount' => 20,
        'category' => 'operational',
        'spent_at' => $startDate->toDateString(),
    ]);
    Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'Excluded outside-period expense',
        'amount' => 30,
        'category' => 'operational',
        'spent_at' => $startDate->copy()->subDay()->toDateString(),
    ]);
    Expense::create([
        'restaurant_id' => $otherRestaurant->id,
        'title' => 'Excluded foreign expense',
        'amount' => 40,
        'category' => 'operational',
        'spent_at' => $startDate->toDateString(),
    ]);

    $analytics = app(FinancialAnalyticsService::class);
    $period = $analytics->period([
        'startDate' => $startDate->toDateString(),
        'endDate' => $endDate->toDateString(),
    ]);
    parse_str(parse_url($analytics->ordersReportUrl($period, ['verified_sales' => true]), PHP_URL_QUERY), $orderQuery);
    parse_str(parse_url($analytics->ordersReportUrl($period, ['status' => 'cancelled']), PHP_URL_QUERY), $cancelledOrderQuery);
    parse_str(parse_url($analytics->ordersReportUrl($period, ['invoice_status' => 'refunded']), PHP_URL_QUERY), $refundedOrderQuery);
    parse_str(parse_url($analytics->expensesReportUrl($period), PHP_URL_QUERY), $expenseQuery);

    $this->actingAs($admin);
    Filament::setCurrentPanel('restaurant');

    try {
        Livewire::withQueryParams($orderQuery)
            ->test(ListOrders::class)
            ->assertSee('Included verified order')
            ->assertDontSee('Excluded unpaid order')
            ->assertDontSee('Excluded outside-period order')
            ->assertDontSee('Excluded deleted order')
            ->assertDontSee('Excluded foreign order');

        Livewire::withQueryParams($cancelledOrderQuery)
            ->test(ListOrders::class)
            ->assertSee('Included cancelled order')
            ->assertDontSee('Included verified order');

        Livewire::withQueryParams($refundedOrderQuery)
            ->test(ListOrders::class)
            ->assertSee('Included refunded invoice order')
            ->assertDontSee('Included verified order');

        $verifiedOrder->items()->create([
            'product_id' => Product::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'product_name' => 'Drilldown product',
            'quantity' => 1,
            'price' => 10,
            'total' => 10,
        ]);
        $productId = $verifiedOrder->items()->firstOrFail()->product_id;
        parse_str(parse_url($analytics->ordersReportUrl($period, [
            'verified_sales' => true,
            'product_id' => $productId,
        ]), PHP_URL_QUERY), $productOrderQuery);

        Livewire::withQueryParams($productOrderQuery)
            ->test(ListOrders::class)
            ->assertSee('Included verified order')
            ->assertDontSee('Included refunded invoice order');

        Livewire::withQueryParams($expenseQuery)
            ->test(ListExpenses::class)
            ->assertSee('Included period expense')
            ->assertSee('20.00 USD')
            ->assertDontSee('Excluded outside-period expense')
            ->assertDontSee('Excluded foreign expense');
    } finally {
        Filament::setCurrentPanel('sham');
    }
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
