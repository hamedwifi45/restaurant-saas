<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
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

Route::get('/{slug}/product/{id}', [RestaurantController::class, 'showProduct'])->name('product.show');
// التحقق من رمز التتبع (AJAX)
Route::post('/review/verify', [ReviewController::class, 'verify'])->name('review.verify');

// إرسال التقييم (AJAX)
Route::post('/review/store-ajax', [ReviewController::class, 'storeAjax'])->name('review.store.ajax');

Route::resource('themes', ThemeController::class);
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});
    
require __DIR__.'/settings.php';
