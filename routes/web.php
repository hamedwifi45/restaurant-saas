<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ThemeController;


Route::view('/', 'welcome')->name('home');

Route::get('/{slug}', [RestaurantController::class, 'home'])->name('restaurant.home');
Route::get('/{slug}/menu', [RestaurantController::class, 'menu'])->name('restaurant.menu');

// نظام السلة
Route::get('/{slug}/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/{slug}/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/{slug}/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/{slug}/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

// نظام الطلبات والدفع
Route::get('/{slug}/checkout', [OrderController::class, 'checkout'])->name('checkout');
Route::post('/{slug}/checkout', [OrderController::class, 'store'])->name('checkout.store');
Route::get('/{slug}/order/success/{code}', [OrderController::class, 'success'])->name('order.success');
Route::get('/{slug}/track/{code}', [OrderController::class, 'track'])->name('order.track');

// صفحة إدخال رمز التتبع
Route::get('/{slug}/track', [OrderController::class, 'trackForm'])->name('order.track.form');
Route::post('/{slug}/track', [OrderController::class, 'trackSearch'])->name('order.track.search');

// التقييمات
Route::get('/{slug}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::get('/{slug}/review/{trackingCode}', [ReviewController::class, 'create'])->name('review.create');
Route::post('/{slug}/review/{trackingCode}', [ReviewController::class, 'store'])->name('review.store');

// تطبيق العرض (AJAX)
Route::post('/{slug}/apply-offer', [OrderController::class, 'applyOffer'])->name('offer.apply');

// صفحة الفاتورة
Route::get('/{slug}/invoice/{trackingCode}', [InvoiceController::class, 'showInvoice'])->name('order.invoice');

// PWA Routes ديناميكية لكل مطعم
Route::get('/{slug}/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/{slug}/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');

// التحقق من كوبون الخصم (AJAX)
Route::post('/{slug}/apply-coupon', [OrderController::class, 'applyCoupon'])->name('coupon.apply');

Route::get('/{slug}/product/{id}', [RestaurantController::class, 'showProduct'])->name('product.show');
// التحقق من رمز التتبع (AJAX)
Route::post('/review/verify', [ReviewController::class, 'verify'])->name('review.verify');

Route::get('/{slug}/offers', [OfferController::class, 'index'])->name('offers.index');

// إرسال التقييم (AJAX)
Route::post('/review/store-ajax', [ReviewController::class, 'storeAjax'])->name('review.store.ajax');

// ==========================================
// لوحة تحكم المطعم (Restaurant Dashboard)
// ==========================================
Route::middleware(['auth', 'verified'])->prefix('restaurant')->name('restaurant.')->group(function () {
    // لوحة التحكم الرئيسية
    Route::livewire('/dashboard', \App\Livewire\Restaurant\Dashboard::class)->name('dashboard');
    
    // إدارة الطلبات
    Route::livewire('/orders', \App\Livewire\Restaurant\Orders::class)->name('orders');
    
    // إدارة المنتجات
    Route::livewire('/products', \App\Livewire\Restaurant\Products::class)->name('products');
    
    // إدارة التصنيفات
    Route::livewire('/categories', \App\Livewire\Restaurant\Categories::class)->name('categories');
    
    // إدارة العروض
    Route::livewire('/offers', \App\Livewire\Restaurant\Offers::class)->name('offers');
    
    // إدارة أكواد الخصم
    Route::livewire('/coupons', \App\Livewire\Restaurant\Coupons::class)->name('coupons');
    
    // إدارة التقييمات
    Route::livewire('/reviews', \App\Livewire\Restaurant\Reviews::class)->name('reviews');
    
    // إعدادات المطعم
    Route::livewire('/settings', \App\Livewire\Restaurant\Settings::class)->name('settings');
    
    // تخصيص الثيم
    Route::livewire('/theme-customizer', \App\Livewire\Restaurant\ThemeCustomizer::class)->name('theme-customizer');
});

Route::resource('themes', ThemeController::class);
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});
    
require __DIR__.'/settings.php';
