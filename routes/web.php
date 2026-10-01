<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KdsController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\Restaurant\ThemeSettingsController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantDashboardController;
use App\Http\Controllers\RestaurantTableController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. المسارات العامة
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| 2. مسارات لوحة التحكم والأدوات (Auth Required)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // إدارة الثيمات (Themes)
    Route::middleware('can:access-theme-editor')->group(function () {
        Route::get('themes/{theme}/preview', [ThemeController::class, 'preview'])->name('themes.preview');
        Route::post('themes/{theme}/activate', [ThemeController::class, 'activate'])->name('themes.activate');
        Route::post('themes/{theme}/clone', [ThemeController::class, 'clone'])->name('themes.clone');
        Route::post('themes/{theme}/reset-settings', [ThemeController::class, 'resetSettings'])->name('themes.reset-settings');
        Route::resource('themes', ThemeController::class);
    });

    // واجهة المطبخ (KDS)
    Route::redirect('/{slug}/kds', '/kitchen/{slug}/display')->middleware('can:access-kds');
    Route::prefix('kitchen')->name('kitchen.')->middleware('can:access-kds')->group(function () {
        Route::get('/{slug}/display', [KdsController::class, 'index'])->name('display');
        Route::post('/orders/{orderId}/status', [KdsController::class, 'updateStatus'])->name('orders.updateStatus');
    });

    // API لوحة تحكم المطعم والطاولات
    Route::prefix('restaurant')->group(function () {
        Route::get('/tables/{table}/qr', [RestaurantTableController::class, 'qr'])->name('restaurant.table.qr');

        Route::prefix('api')->group(function () {
            Route::get('/theme/settings', [ThemeSettingsController::class, 'getSettings'])->middleware('can:access-theme-editor')->name('restaurant.theme.settings.get');
            Route::post('/theme/settings', [ThemeSettingsController::class, 'updateSettings'])->middleware('can:access-theme-editor')->name('restaurant.theme.settings.update');
            Route::get('/dashboard/summary', [RestaurantDashboardController::class, 'summary'])->name('restaurant.dashboard.summary');
        });
    });

    // مسارات المندوبين (Drivers)
    Route::prefix('{slug}/driver')->middleware('can:access-driver-panel')->group(function () {
        Route::get('/orders', [DriverController::class, 'index'])->name('driver.orders');
        Route::get('/dashboard', [DriverController::class, 'dashboard'])->name('driver.dashboard');

        Route::get('/orders/{order}', [DriverController::class, 'show'])->name('driver.order.show');
        Route::post('/orders/{order}/accept', [DriverController::class, 'acceptOrder'])->name('orders.accept');
        Route::post('/orders/{order}/location', [DriverController::class, 'updateLocation'])->name('driver.location.update');
        Route::post('/orders/{order}/status', [DriverController::class, 'updateStatus'])->name('driver.status.update');
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
    Route::get('/product/{id}', [RestaurantController::class, 'showProduct'])->name('product.show');
    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/table/{token}', [RestaurantTableController::class, 'enter'])->name('restaurant.table.menu');

    // السلة (Cart)
    Route::prefix('cart')->as('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/add', [CartController::class, 'add'])->name('add');
        Route::post('/update', [CartController::class, 'update'])->name('update');
        Route::post('/remove', [CartController::class, 'remove'])->name('remove');
    });

    // الطلب والدفع (Checkout & Orders)
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/order/success/{code}', [OrderController::class, 'success'])->name('order.success');
    Route::post('/apply-offer', [OrderController::class, 'applyOffer'])->name('offer.apply');
    Route::post('/apply-coupon', [OrderController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('coupon.apply');

    // تتبع الطلبات
    Route::get('/track', [OrderController::class, 'trackForm'])->name('order.track.form');
    Route::post('/track', [OrderController::class, 'trackSearch'])->name('order.track.search');
    Route::get('/track/{code}', [OrderController::class, 'track'])->name('order.track');
    Route::get('/track/{code}/location', [OrderController::class, 'trackingLocation'])->name('order.track.location');

    // التقييمات والفواتير
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/review/{trackingCode}', [ReviewController::class, 'create'])->name('review.create');
    Route::post('/review/{trackingCode}', [ReviewController::class, 'store'])->name('review.store');
    Route::get('/invoice/{trackingCode}', [InvoiceController::class, 'showInvoice'])->name('order.invoice');

    // PWA Manifest & Service Worker
    Route::get('/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
    Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');
});

/*
|--------------------------------------------------------------------------
| 5. الإعدادات والملفات الإضافية
|--------------------------------------------------------------------------
*/
require __DIR__.'/settings.php';
