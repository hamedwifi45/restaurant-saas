<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ThemeController;


Route::view('/', 'welcome')->name('home');

// واجهة المطاعم
Route::get('/restaurant/{slug}', [RestaurantController::class, 'home'])->name('restaurant.home');
Route::get('/restaurant/{slug}/menu', [RestaurantController::class, 'menu'])->name('restaurant.menu');

// نظام السلة
Route::get('/{slug}/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/{slug}/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/{slug}/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/{slug}/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

// الطلبات والدفع
Route::get('/{slug}/checkout', [OrderController::class, 'checkout'])->name('checkout');
Route::post('/{slug}/checkout', [OrderController::class, 'store'])->name('checkout.store');
Route::get('/{slug}/order/success/{code}', [OrderController::class, 'success'])->name('order.success');

Route::resource('themes', ThemeController::class);
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});
    
require __DIR__.'/settings.php';
