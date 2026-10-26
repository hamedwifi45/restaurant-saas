<?php

use App\Http\Controllers\AiAgentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerBroadcastAuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverRatingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KdsController;
use App\Http\Controllers\LanPairingSignalController;
use App\Http\Controllers\LocalOrderIntakeController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPushSubscriptionController;
use App\Http\Controllers\PosReceiptController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\Restaurant\ThemeSettingsController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantDashboardController;
use App\Http\Controllers\RestaurantOrderAvailabilityController;
use App\Http\Controllers\RestaurantTableController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ThemeController;
use App\Http\Middleware\EnsureLocalRestaurantPairing;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. المسارات العامة
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('home');
Route::post('/broadcasting/customer-auth', [CustomerBroadcastAuthController::class, 'authorizeCustomerChannel'])
    ->middleware('throttle:60,1')
    ->name('broadcasting.customer-auth');

/*
|--------------------------------------------------------------------------
| 2. مسارات لوحة التحكم والأدوات (Auth Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::redirect('/dashboard', '/')->name('dashboard');

    Route::get('/{slug}/pos', function (string $slug) {
        Gate::authorize('access-pos', $slug);
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('pos.index', [
            'restaurant' => $restaurant,
            'pageTitle' => 'نقطة البيع',
        ]);
    })->middleware(EnsureLocalRestaurantPairing::class)->name('pos.index');

    Route::get('/{slug}/pos/orders/{trackingCode}/receipt', [PosReceiptController::class, 'show'])
        ->middleware(EnsureLocalRestaurantPairing::class)
        ->name('pos.receipt');

    Route::get('/pos/{slug}', function (string $slug) {
        Gate::authorize('access-pos', $slug);

        return redirect()->route('pos.index', ['slug' => $slug]);
    })->name('pos.legacy');

    Route::get('/pos/{slug}/orders/{trackingCode}/receipt', function (
        string $slug,
        string $trackingCode,
    ) {
        Gate::authorize('access-pos', $slug);

        return redirect()->route('pos.receipt', [
            'slug' => $slug,
            'trackingCode' => $trackingCode,
        ]);
    })->name('pos.receipt.legacy');

    // إدارة الثيمات (Themes)
    Route::middleware('can:access-theme-editor')->group(function () {
        Route::get('themes/{theme}/preview', [ThemeController::class, 'preview'])->name('themes.preview');
        Route::post('themes/{theme}/activate', [ThemeController::class, 'activate'])->name('themes.activate');
        Route::post('themes/{theme}/clone', [ThemeController::class, 'clone'])->name('themes.clone');
        Route::post('themes/{theme}/reset-settings', [ThemeController::class, 'resetSettings'])->name('themes.reset-settings');
        Route::resource('themes', ThemeController::class);
    });

    // Local restaurant paths are slug-first; tenant-host routes are deferred.
    Route::get('/{slug}/kds', [KdsController::class, 'index'])
        ->middleware('can:access-kds')
        ->middleware(EnsureLocalRestaurantPairing::class)
        ->name('kitchen.display');
    Route::get('/{slug}/kds/orders', [KdsController::class, 'orders'])
        ->middleware('can:access-kds')
        ->middleware(EnsureLocalRestaurantPairing::class)
        ->name('kitchen.orders.index');
    Route::post('/{slug}/kds/orders/acknowledgements', [KdsController::class, 'acknowledgeOrders'])
        ->middleware(['can:access-kds', EnsureLocalRestaurantPairing::class, 'throttle:120,1'])
        ->name('kitchen.orders.acknowledge');
    Route::post('/{slug}/kds/orders/{orderId}/status', [KdsController::class, 'updateStatusForRestaurant'])
        ->middleware('can:access-kds')
        ->middleware(EnsureLocalRestaurantPairing::class)
        ->name('kitchen.orders.updateStatusForRestaurant');
    Route::post('/{slug}/kds/orders/{orderId}/reject', [KdsController::class, 'rejectOrderForRestaurant'])
        ->middleware('can:access-kds')
        ->middleware(EnsureLocalRestaurantPairing::class)
        ->name('kitchen.orders.rejectForRestaurant');

    Route::post('/api/local/{slug}/orders', [LocalOrderIntakeController::class, 'store'])
        ->middleware([EnsureLocalRestaurantPairing::class, 'throttle:60,1'])
        ->name('local.orders.store');

    Route::get('/{slug}/lan-bridge/signal', [LanPairingSignalController::class, 'pollSignal'])
        ->middleware('throttle:60,1')
        ->name('lan.signal.poll');
    Route::post('/{slug}/lan-bridge/signal', [LanPairingSignalController::class, 'publishSignal'])
        ->middleware('throttle:30,1')
        ->name('lan.signal.publish');
    Route::prefix('kitchen')->name('kitchen.')->middleware('can:access-kds')->group(function () {
        Route::get('/{slug}/display', function (string $slug) {
            Gate::authorize('access-kds');
            abort_unless(
                Restaurant::query()
                    ->where('slug', $slug)
                    ->whereKey(auth()->user()->restaurant_id)
                    ->where('is_active', true)
                    ->exists(),
                404,
            );

            return redirect()->route('kitchen.display', ['slug' => $slug]);
        })->name('legacy-display');
        Route::post('/orders/{orderId}/status', [KdsController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::post('/orders/{orderId}/reject', [KdsController::class, 'rejectOrder'])->name('orders.reject');
    });

    // API لوحة تحكم المطعم والطاولات
    Route::prefix('restaurant')->group(function () {
        Route::get('/tables/{table}/qr', [RestaurantTableController::class, 'qr'])->name('restaurant.table.qr');
        Route::get('/orders/{order}/payment-receipt', [OrderController::class, 'downloadPaymentReceipt'])
            ->name('restaurant.orders.payment-receipt');
        Route::post('/{slug}/orders/availability', [RestaurantOrderAvailabilityController::class, 'update'])
            ->name('restaurant.orders.availability');

        Route::prefix('api')->group(function () {
            Route::get('/theme/settings', [ThemeSettingsController::class, 'getSettings'])->middleware('can:access-theme-editor')->name('restaurant.theme.settings.get');
            Route::post('/theme/settings', [ThemeSettingsController::class, 'updateSettings'])->middleware('can:access-theme-editor')->name('restaurant.theme.settings.update');
            Route::get('/dashboard/summary', [RestaurantDashboardController::class, 'summary'])->name('restaurant.dashboard.summary');
        });
    });

    // مسارات المندوبين (Drivers)
    Route::prefix('{slug}/driver')->group(function () {
        Route::middleware('can:access-driver-orders,slug')->group(function () {
            Route::get('/orders', [DriverController::class, 'index'])->name('driver.orders');
            Route::get('/orders/{order}', [DriverController::class, 'show'])->name('driver.order.show');
        });

        Route::middleware('can:access-driver-dashboard,slug')->group(function () {
            Route::get('/dashboard', [DriverController::class, 'dashboard'])->name('driver.dashboard');
        });

        Route::middleware('can:access-driver-panel,slug')->group(function () {
            Route::post('/orders/{order}/accept', [DriverController::class, 'acceptOrder'])->name('orders.accept');
            Route::post('/orders/{order}/location', [DriverController::class, 'updateLocation'])->name('driver.location.update');
            Route::post('/orders/{order}/status', [DriverController::class, 'updateStatus'])->name('driver.status.update');
            Route::post('/orders/{order}/arrived', [DriverController::class, 'markArrived'])->name('driver.order.arrived');
        });
    });
});

Route::get('{slug}/driver/orders/{order:tracking_code}/tracking', [DriverController::class, 'tracking'])->name('driver.order.tracking');

/*
|--------------------------------------------------------------------------
| 3. مسارات التقييم العامة (AJAX)
|--------------------------------------------------------------------------
*/
Route::post('/review/verify', [ReviewController::class, 'verify'])->name('review.verify');
Route::post('/review/store-ajax', [ReviewController::class, 'storeAjax'])->name('review.store.ajax');

/*
|--------------------------------------------------------------------------
| 4. مسارات المطعم العامة والعملاء ({slug})
|--------------------------------------------------------------------------
*/
Route::prefix('{slug}')->group(function () {

    // الرئيسية والمنيو والمنتجات والعروض
    Route::get('/', [RestaurantController::class, 'home'])->name('restaurant.home');
    Route::get('/menu', [RestaurantController::class, 'menu'])->name('restaurant.menu');
    Route::get('/product/{slugP}', [RestaurantController::class, 'showProduct'])->name('product.show');
    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/table/{token}', [RestaurantTableController::class, 'enter'])->name('restaurant.table.menu');
    Route::post('/table/leave', [RestaurantTableController::class, 'leave'])->name('restaurant.table.leave');

    // السلة (Cart)
    Route::prefix('cart')->as('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/add', [CartController::class, 'add'])->name('add');
        Route::post('/update', [CartController::class, 'update'])->name('update');
        Route::post('/remove', [CartController::class, 'remove'])->name('remove');
    });

    // الطلب والدفع (Checkout & Orders)
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/delivery-options', [AiAgentController::class, 'evaluateDeliveryOptions'])
        ->middleware('throttle:30,1')
        ->name('checkout.delivery-options');
    Route::post('/checkout/table-availability', [OrderController::class, 'tableAvailability'])
        ->middleware('throttle:30,1')
        ->name('checkout.table-availability');
    Route::post('/checkout', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/order/success/{code}', [OrderController::class, 'success'])->name('order.success');
    Route::post('/apply-offer', [OrderController::class, 'applyOffer'])->name('offer.apply');
    Route::post('/apply-coupon', [OrderController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('coupon.apply');

    // تتبع الطلبات
    Route::get('/track', [OrderController::class, 'trackForm'])->name('order.track.form');
    Route::post('/track', [OrderController::class, 'trackSearch'])->name('order.track.search');
    Route::get('/track/{code}', [OrderController::class, 'track'])->name('order.track');
    Route::get('/track/{code}/location', [OrderController::class, 'trackingLocation'])->name('order.track.location');
    Route::post('/track/{order:tracking_code}/push-subscription', [OrderPushSubscriptionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('order.push-subscriptions.store');
    Route::post('/track/{order:tracking_code}/driver-rating', [DriverRatingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('order.driver-rating.store');

    // التقييمات والفواتير
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/review/{trackingCode}', [ReviewController::class, 'create'])->name('review.create');
    Route::post('/review/{trackingCode}', [ReviewController::class, 'store'])->name('review.store');
    Route::get('/invoice/{trackingCode}', [InvoiceController::class, 'showInvoice'])->name('order.invoice');

    // PWA Manifest & Service Worker
    Route::get('/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
    Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
    // الوكيل الذكي المدمج (AI Agent)
    Route::post('/api/ai-agent/assist', [AiAgentController::class, 'assist'])
        ->middleware('throttle:20,1')
        ->name('restaurant.ai-agent.assist');
});

Route::post('/api/ai-agent/assist', [AiAgentController::class, 'assist'])
    ->middleware('throttle:20,1')
    ->name('ai-agent.assist');

/*
|--------------------------------------------------------------------------
| 5. الإعدادات والملفات الإضافية
|--------------------------------------------------------------------------
*/
require __DIR__.'/settings.php';
