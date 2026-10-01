<?php

use App\Filament\Restaurant\Resources\Coupons\CouponResource;
use App\Filament\Restaurant\Resources\Drivers\DriverResource;
use App\Filament\Restaurant\Resources\Expenses\ExpenseResource;
use App\Filament\Restaurant\Resources\Invoices\InvoiceResource;
use App\Filament\Restaurant\Resources\Orders\OrderResource;
use App\Filament\Restaurant\Resources\Products\ProductResource;
use App\Filament\Restaurant\Resources\RestaurantTables\RestaurantTableResource;
use App\Filament\Restaurant\Widgets\FinancialOverviewWidget;
use App\Livewire\Settings\Profile;
use App\Models\Coupon;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;

// يفحص BOLA عبر معرف الطلب في واجهتي العرض والاستلام ومسارات التتبع والتعديل.
test('drivers cannot read or claim orders belonging to another restaurant', function () {
    $restaurantA = Restaurant::factory()->create(['plan' => 'pro']);
    $restaurantB = Restaurant::factory()->create(['plan' => 'pro']);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurantA->id,
    ]);
    $foreignOrder = Order::factory()->create([
        'restaurant_id' => $restaurantB->id,
        'driver_id' => null,
        'status' => 'ready',
    ]);
    $ownOrder = Order::factory()->create([
        'restaurant_id' => $restaurantA->id,
        'driver_id' => $driver->id,
        'status' => 'ready',
    ]);

    $this->actingAs($driver)
        ->get(route('driver.order.show', [$restaurantB->slug, $foreignOrder->id]))
        ->assertForbidden();

    $this->post(route('orders.accept', [$restaurantB->slug, $foreignOrder->id]))
        ->assertForbidden();

    $this->get(route('driver.order.tracking', [$restaurantB->slug, $foreignOrder->tracking_code]))
        ->assertForbidden();

    $this->post(route('driver.status.update', [$restaurantB->slug, $ownOrder->id]), [
        'status' => 'on_way',
    ])->assertForbidden();

    $this->post(route('driver.location.update', [$restaurantB->slug, $ownOrder->id]), [
        'latitude' => 33.5,
        'longitude' => 36.3,
    ])->assertForbidden();

    expect($foreignOrder->fresh()->driver_id)->toBeNull()
        ->and($foreignOrder->fresh()->status)->toBe('ready')
        ->and($ownOrder->fresh()->status)->toBe('ready')
        ->and($ownOrder->fresh()->driver_latitude)->toBeNull();
});

// يتحقق من أن slug ومعرف السجل لا يسمحان بفتح طلب أو منتج أو فاتورة أو مصروف لمستأجر آخر.
test('direct tenant resource URLs do not resolve foreign tenant records', function () {
    $restaurantA = Restaurant::factory()->create(['plan' => 'pro']);
    $restaurantB = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurantA->id,
    ]);
    $foreignOrder = Order::factory()->create(['restaurant_id' => $restaurantB->id]);
    $foreignProduct = Product::factory()->create(['restaurant_id' => $restaurantB->id]);
    $foreignInvoice = Invoice::factory()->create([
        'order_id' => $foreignOrder->id,
        'restaurant_id' => $restaurantB->id,
    ]);
    $foreignCoupon = Coupon::factory()->create(['restaurant_id' => $restaurantB->id]);
    $foreignExpense = Expense::create([
        'restaurant_id' => $restaurantB->id,
        'title' => 'Foreign expense',
        'amount' => 75,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);

    $this->actingAs($admin);

    $this->get('/restaurant/orders/'.$foreignOrder->id.'/edit')->assertNotFound();
    $this->get('/restaurant/products/'.$foreignProduct->id.'/edit')->assertNotFound();
    $this->get('/restaurant/invoices/'.$foreignInvoice->id.'/edit')->assertNotFound();
    $this->get('/restaurant/expenses/'.$foreignExpense->id.'/edit')->assertNotFound();
    $this->get('/'.$restaurantA->slug.'/product/'.$foreignProduct->id)->assertNotFound();
    $this->get('/'.$restaurantA->slug.'/track/'.$foreignOrder->tracking_code)->assertNotFound();
    $this->get('/'.$restaurantA->slug.'/invoice/'.$foreignOrder->tracking_code)->assertNotFound();

    foreach (['put', 'patch', 'delete'] as $method) {
        $this->{$method}('/restaurant/orders/'.$foreignOrder->id.'/edit')->assertStatus(405);
        $this->{$method}('/restaurant/products/'.$foreignProduct->id.'/edit')->assertStatus(405);
        $this->{$method}('/restaurant/invoices/'.$foreignInvoice->id.'/edit')->assertStatus(405);
        $this->{$method}('/restaurant/expenses/'.$foreignExpense->id.'/edit')->assertStatus(405);
    }

    expect(OrderResource::getEloquentQuery()->whereKey($foreignOrder->id)->exists())->toBeFalse()
        ->and(ProductResource::getEloquentQuery()->whereKey($foreignProduct->id)->exists())->toBeFalse()
        ->and(InvoiceResource::getEloquentQuery()->whereKey($foreignInvoice->id)->exists())->toBeFalse()
        ->and(ExpenseResource::getEloquentQuery()->whereKey($foreignExpense->id)->exists())->toBeFalse()
        ->and(CouponResource::getEloquentQuery()->whereKey($foreignCoupon->id)->exists())->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $foreignOrder))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $foreignOrder))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $foreignProduct))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $foreignProduct))->toBeFalse()
        ->and(ExpenseResource::canEdit($foreignExpense))->toBeFalse()
        ->and(ExpenseResource::canDelete($foreignExpense))->toBeFalse()
        ->and(Coupon::query()->whereKey($foreignCoupon->id)->where('restaurant_id', $restaurantA->id)->exists())->toBeFalse();
});

// يفحص أن ملف robots.txt لا يعلن عن مسارات الإدارة أو نقاط التشخيص الحساسة.
test('robots file does not disclose sensitive administration or diagnostic paths', function () {
    $robotsPath = public_path('robots.txt');

    expect(file_exists($robotsPath))->toBeTrue();

    $robotsContents = file_get_contents($robotsPath);

    expect($robotsContents)->not->toContain('/sham')
        ->and($robotsContents)->not->toContain('/restaurant')
        ->and($robotsContents)->not->toContain('/telescope')
        ->and($robotsContents)->not->toContain('/horizon')
        ->and($robotsContents)->not->toContain('/.env');
});

// يتحقق من أن ملفات البيئة وواجهات التشخيص غير موجودة لجميع الأدوار والزوار.
test('sensitive reconnaissance paths are unavailable to guests and every role', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $principals = [
        null,
        User::factory()->create(['role' => 'customer', 'restaurant_id' => $restaurant->id]),
        User::factory()->create(['role' => 'kitchen_staff', 'restaurant_id' => $restaurant->id]),
        User::factory()->create(['role' => 'driver', 'restaurant_id' => $restaurant->id]),
        User::factory()->create(['role' => 'restaurant_admin', 'restaurant_id' => $restaurant->id]),
        User::factory()->create(['role' => 'super_admin', 'restaurant_id' => null]),
    ];
    $paths = ['/.env', '/.git', '/api/v1/debug', '/telescope', '/horizon', '/phpinfo'];

    foreach ($principals as $principal) {
        Auth::logout();

        if ($principal) {
            $this->actingAs($principal);
        }

        foreach ($paths as $path) {
            $this->get($path)->assertNotFound();
        }
    }
});

// يتحقق بمصفوفة أدوار من أن إدارة المستخدمين متاحة حصراً لمدير النظام.
test('only super admins can access user management', function (string $role, int $expectedStatus) {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $user = User::factory()->create([
        'role' => $role,
        'restaurant_id' => $role === 'super_admin' ? null : $restaurant->id,
    ]);

    $this->actingAs($user)
        ->get('/sham/users')
        ->assertStatus($expectedStatus);
})->with([
    'customer is denied' => ['customer', 403],
    'kitchen staff is denied' => ['kitchen_staff', 403],
    'driver is denied' => ['driver', 403],
    'restaurant admin is denied' => ['restaurant_admin', 403],
    'super admin is allowed' => ['super_admin', 200],
]);

// يفحص تجاهل حقول السعر والخصم والمستأجر المرسلة يدوياً أثناء checkout.
test('checkout ignores forged tenant price role and discount fields', function () {
    $restaurantA = Restaurant::factory()->create([
        'delivery_fee' => 5,
        'tax_percentage' => 15,
    ]);
    $restaurantB = Restaurant::factory()->create();
    $product = Product::factory()->create([
        'restaurant_id' => $restaurantA->id,
        'price' => 80,
    ]);
    $foreignCoupon = Coupon::factory()->create(['restaurant_id' => $restaurantB->id]);
    $customer = User::factory()->create([
        'role' => 'customer',
        'restaurant_id' => $restaurantA->id,
    ]);

    $this->actingAs($customer)->withSession([
        'cart_'.$restaurantA->id => [$product->id => ['qty' => 2]],
    ])->post(route('checkout.store', $restaurantA->slug), [
        'name' => 'Tampering Customer',
        'phone' => '0930123456',
        'address' => 'Customer address',
        'latitude' => 33.5,
        'longitude' => 36.3,
        'payment_method' => 'cash',
        'restaurant_id' => $restaurantB->id,
        'role' => 'super_admin',
        'coupon_id' => $foreignCoupon->id,
        'coupon_code' => $foreignCoupon->code,
        'price' => 0.01,
        'discount' => 9999,
        'coupon_discount' => 9999,
        'tax_amount' => 0,
        'total_amount' => 0.01,
        'final_amount' => 0.01,
    ])->assertRedirect();

    $order = Order::query()->sole();
    $item = Order_item::query()->sole();

    expect($order->restaurant_id)->toBe($restaurantA->id)
        ->and($order->customer_name)->toBe('Tampering Customer')
        ->and((float) $order->subtotal)->toBe(160.0)
        ->and((float) $order->discount)->toBe(0.0)
        ->and((float) $order->coupon_discount)->toBe(0.0)
        ->and((float) $order->final_amount)->toBe(189.0)
        ->and((float) $item->price)->toBe(80.0)
        ->and($customer->fresh()->role)->toBe('customer')
        ->and($customer->fresh()->restaurant_id)->toBe($restaurantA->id);
});

// يتحقق من أن نموذج الملف الشخصي لا يقبل تصعيد الدور أو نقل المستخدم لمستأجر آخر.
test('profile updates accept only profile fields and preserve privileged identity fields', function () {
    $restaurantA = Restaurant::factory()->create();
    $restaurantB = Restaurant::factory()->create();
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurantA->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(Profile::class)
        ->set('name', 'Renamed Restaurant Admin')
        ->set('email', 'renamed-admin@example.test')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect(fn () => Livewire::test(Profile::class)->set('role', 'super_admin'))
        ->toThrow(PublicPropertyNotFoundException::class);
    expect(fn () => Livewire::test(Profile::class)->set('restaurant_id', $restaurantB->id))
        ->toThrow(PublicPropertyNotFoundException::class);

    expect($admin->fresh()->name)->toBe('Renamed Restaurant Admin')
        ->and($admin->fresh()->role)->toBe('restaurant_admin')
        ->and($admin->fresh()->restaurant_id)->toBe($restaurantA->id)
        ->and(User::query()->whereKey($admin->id)->where('restaurant_id', $restaurantB->id)->exists())->toBeFalse();
});

// يفحص عزل جلسة السلة ورفض إدخال منتج من مستأجر آخر في checkout.
test('tampered cart sessions cannot checkout another restaurants products', function () {
    $restaurantA = Restaurant::factory()->create();
    $restaurantB = Restaurant::factory()->create();
    $foreignProduct = Product::factory()->create(['restaurant_id' => $restaurantB->id]);

    $this->withSession([
        'cart_'.$restaurantA->id => [$foreignProduct->id => ['qty' => 1]],
    ])->post(route('checkout.store', $restaurantA->slug), [
        'name' => 'Session Tampering Customer',
        'phone' => '0930123456',
        'address' => 'Delivery address',
        'latitude' => 33.5,
        'longitude' => 36.3,
        'payment_method' => 'cash',
        'restaurant_id' => $restaurantB->id,
    ])->assertRedirect();

    expect(Order::query()->count())->toBe(0)
        ->and(Invoice::query()->count())->toBe(0);
});

// يتحقق من تجاهل هيدر المستأجر المزوّر في إحصاءات لوحة التحكم.
test('dashboard API derives tenant statistics only from the authenticated session', function () {
    $restaurantA = Restaurant::factory()->create(['plan' => 'pro']);
    $restaurantB = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurantA->id,
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurantA->id,
        'customer_name' => 'Tenant A Customer',
        'total_amount' => 125,
        'created_at' => now(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $restaurantB->id,
        'customer_name' => 'Tenant B Secret Customer',
        'total_amount' => 9000,
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withHeader('X-Restaurant-Id', (string) $restaurantB->id)
        ->getJson('/restaurant/api/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('summary.sales_today', 125)
        ->assertJsonPath('summary.active_orders', 1)
        ->assertJsonMissing(['customer' => 'Tenant B Secret Customer']);
});

// يثبت أن تسجيل الدخول يوقف التخمين المتكرر بعد حد Fortify المكوّن من خمس محاولات.
test('login brute force attempts are throttled by Fortify', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => 'unknown-user@example.test',
            'password' => 'incorrect-password',
        ]);
    }

    $this->post('/login', [
        'email' => 'unknown-user@example.test',
        'password' => 'incorrect-password',
    ])->assertStatus(429);
});

// يفحص خنق الطلبات المتكررة إلى مسار إتمام الشراء.
test('checkout requests are rate limited', function () {
    $restaurant = Restaurant::factory()->create();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->post(route('checkout.store', $restaurant->slug), []);
    }

    $this->post(route('checkout.store', $restaurant->slug), [])
        ->assertStatus(429);
});

// يفحص خنق محاولات تخمين رموز القسائم على مستأجر واحد.
test('coupon verification requests are rate limited', function () {
    $restaurant = Restaurant::factory()->create();
    $payload = ['coupon_code' => 'INVALID', 'subtotal' => 100];

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->post(route('coupon.apply', $restaurant->slug), $payload);
    }

    $this->post(route('coupon.apply', $restaurant->slug), $payload)
        ->assertStatus(429);
});

// يختبر منع موظف المطبخ من قراءة طلب مستأجر آخر أو تغيير حالته عبر KDS.
test('kitchen staff cannot view or update another restaurants orders through KDS or a tampered slug', function () {
    $restaurantA = Restaurant::factory()->create(['plan' => 'pro']);
    $restaurantB = Restaurant::factory()->create(['plan' => 'pro']);
    $kitchenStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $restaurantA->id,
    ]);
    $foreignOrder = Order::factory()->create([
        'restaurant_id' => $restaurantB->id,
        'status' => 'pending',
    ]);

    $this->actingAs($kitchenStaff)
        ->get(route('kitchen.display', $restaurantB->slug))
        ->assertForbidden();

    $this->post(route('kitchen.orders.updateStatus', $foreignOrder->tracking_code), [
        'status' => 'preparing',
    ])->assertForbidden();

    expect($foreignOrder->fresh()->status)->toBe('pending');
});

// يتحقق من أن استعلامات موارد الإدارة لا تعرض سجلات أي مستأجر آخر.
test('restaurant resources exclude records belonging to another tenant', function () {
    $restaurantA = Restaurant::factory()->create(['plan' => 'pro']);
    $restaurantB = Restaurant::factory()->create(['plan' => 'pro']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurantA->id,
    ]);
    $ownDriver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurantA->id,
    ]);
    User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurantB->id,
    ]);
    $ownOrder = Order::factory()->create(['restaurant_id' => $restaurantA->id]);
    $foreignOrder = Order::factory()->create(['restaurant_id' => $restaurantB->id]);
    $ownInvoice = Invoice::factory()->create([
        'order_id' => $ownOrder->id,
        'restaurant_id' => $restaurantA->id,
    ]);
    Invoice::factory()->create([
        'order_id' => $foreignOrder->id,
        'restaurant_id' => $restaurantB->id,
    ]);
    $ownExpense = Expense::create([
        'restaurant_id' => $restaurantA->id,
        'title' => 'Local supplies',
        'amount' => 25,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);
    Expense::create([
        'restaurant_id' => $restaurantB->id,
        'title' => 'Foreign supplies',
        'amount' => 50,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);
    $ownTable = RestaurantTable::factory()->create(['restaurant_id' => $restaurantA->id]);
    RestaurantTable::factory()->create(['restaurant_id' => $restaurantB->id]);

    $this->actingAs($admin);

    expect(DriverResource::getEloquentQuery()->pluck('id')->all())->toBe([$ownDriver->id])
        ->and(InvoiceResource::getEloquentQuery()->pluck('id')->all())->toBe([$ownInvoice->id])
        ->and(ExpenseResource::getEloquentQuery()->pluck('id')->all())->toBe([$ownExpense->id])
        ->and(RestaurantTableResource::getEloquentQuery()->pluck('id')->all())->toBe([$ownTable->id]);
});

// يتحقق من تطبيق قيود خطة Starter على التحليلات وKDS وإعدادات المظهر.
test('starter restaurants are denied financial analytics KDS and theme settings', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'starter']);
    $admin = User::factory()->create([
        'role' => 'restaurant_admin',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($admin)
        ->get('/restaurant/financial-analytics')
        ->assertForbidden();

    $this->get('/'.$restaurant->slug.'/kds')->assertForbidden();
    $this->get('/restaurant/theme-settings')->assertForbidden();
});

// يمنع المندوب من دخول لوحة المطعم أو شاشة المطبخ.
test('drivers cannot access restaurant admin pages or KDS', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($driver)
        ->get('/restaurant')
        ->assertForbidden();

    $this->get('/'.$restaurant->slug.'/kds')->assertForbidden();
});

// يقصر صلاحيات موظف المطبخ على KDS ويمنعه من المالية وإدارة المستخدمين.
test('kitchen staff can access KDS but not financial pages or user management', function () {
    $restaurant = Restaurant::factory()->create(['plan' => 'pro']);
    $kitchenStaff = User::factory()->create([
        'role' => 'kitchen_staff',
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($kitchenStaff)
        ->get(route('kitchen.display', $restaurant->slug))
        ->assertOk();

    $this->get('/restaurant/financial-analytics')->assertForbidden();
    $this->get('/sham/users')->assertForbidden();
});

// يطابق معادلة الطلب والفاتورة عبر سيناريوهات متعددة للضريبة والخصم والتوصيل.
test('checkout persists correct order and invoice totals for varying baskets', function (array $scenario) {
    $restaurant = Restaurant::factory()->create([
        'delivery_fee' => $scenario['delivery_fee'],
        'tax_percentage' => 15,
    ]);
    $product = Product::factory()->create([
        'restaurant_id' => $restaurant->id,
        'price' => $scenario['unit_price'],
    ]);
    $coupon = Coupon::factory()->create([
        'restaurant_id' => $restaurant->id,
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => 0,
    ]);
    $subtotal = $scenario['unit_price'] * $scenario['quantity'];
    $tax = round($subtotal * 0.15, 2);
    $discount = round($subtotal * 0.10, 2);
    $expectedFinalAmount = round($subtotal + $tax + $scenario['delivery_fee'] - $discount, 2);

    $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => $scenario['quantity']],
        ],
    ])->post(route('checkout.store', $restaurant->slug), [
        'name' => 'Audit Customer',
        'phone' => '0930123456',
        'address' => 'Delivery address',
        'latitude' => 24.7,
        'longitude' => 46.7,
        'payment_method' => 'cash',
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
    ])->assertRedirect();

    $order = Order::query()->sole();
    $invoice = Invoice::query()->sole();

    expect((float) $order->subtotal)->toBe((float) $subtotal)
        ->and((float) $order->tax_amount)->toBe((float) $tax)
        ->and((float) $order->delivery_fee)->toBe((float) $scenario['delivery_fee'])
        ->and((float) $order->coupon_discount)->toBe((float) $discount)
        ->and((float) $order->final_amount)->toBe((float) $expectedFinalAmount)
        ->and((float) $invoice->subtotal)->toBe((float) $subtotal)
        ->and((float) $invoice->tax_amount)->toBe((float) $tax)
        ->and((float) $invoice->delivery_fee)->toBe((float) $scenario['delivery_fee'])
        ->and((float) $invoice->final_amount)->toBe(round((float) $invoice->subtotal + (float) $invoice->tax_amount + (float) $invoice->delivery_fee - (float) $order->coupon_discount, 2))
        ->and((float) $invoice->final_amount)->toBe((float) $expectedFinalAmount)
        ->and((float) $invoice->total_amount)->toBe((float) $expectedFinalAmount);
})->with([
    'two-item basket with delivery' => [[
        'unit_price' => 100,
        'quantity' => 2,
        'delivery_fee' => 10,
    ]],
    'fractional tax and discount' => [[
        'unit_price' => 75,
        'quantity' => 1,
        'delivery_fee' => 0,
    ]],
    'larger basket with non-default delivery' => [[
        'unit_price' => 49,
        'quantity' => 3,
        'delivery_fee' => 15,
    ]],
]);

// يتأكد من احتساب صافي الربح وعزل بيانات المطاعم الأخرى في الودجت المالي.
test('financial overview reports tenant net profit as gross revenue less expenses', function () {
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
        'discount' => 0,
        'coupon_discount' => 0,
        'final_amount' => 240,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    Expense::create([
        'restaurant_id' => $restaurant->id,
        'title' => 'Local operations',
        'amount' => 40,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);
    Order::factory()->create([
        'restaurant_id' => $otherRestaurant->id,
        'subtotal' => 9000,
        'tax_amount' => 1350,
        'delivery_fee' => 10,
        'payment_status' => 'verified',
        'status' => 'delivered',
        'created_at' => now(),
    ]);
    Expense::create([
        'restaurant_id' => $otherRestaurant->id,
        'title' => 'Foreign operations',
        'amount' => 5000,
        'category' => 'operational',
        'spent_at' => now()->toDateString(),
    ]);

    $this->actingAs($admin);

    Livewire::test(FinancialOverviewWidget::class)
        ->assertSee('240.00 ل.س')
        ->assertSee('40.00 ل.س')
        ->assertSee('200.00 ل.س')
        ->assertDontSee('10,360.00 ل.س')
        ->assertDontSee('5,000.00 ل.س');
});

// يختبر صيغ الهاتف السوري المقبولة والمرفوضة باستخدام مجموعة بيانات.
test('checkout accepts valid Syrian phone numbers and rejects invalid numbers', function (string $phone, bool $isValid) {
    $restaurant = Restaurant::factory()->create();
    $product = Product::factory()->create(['restaurant_id' => $restaurant->id]);

    $response = $this->withSession([
        'cart_'.$restaurant->id => [
            $product->id => ['qty' => 1],
        ],
    ])->post(route('checkout.store', $restaurant->slug), [
        'name' => 'Phone Audit Customer',
        'phone' => $phone,
        'address' => 'Delivery address',
        'latitude' => 24.7,
        'longitude' => 46.7,
        'payment_method' => 'cash',
    ]);

    if ($isValid) {
        $response->assertRedirect();
        expect(Order::query()->count())->toBe(1);

        return;
    }

    $response->assertSessionHasErrors('phone');
    expect(Order::query()->count())->toBe(0);
})->with([
    'local Syrian mobile format' => ['0930123456', true],
    'international Syrian mobile format' => ['+963944112233', true],
    'invalid Syrian landline' => ['011223344', false],
    'too-short number' => ['123456', false],
]);
