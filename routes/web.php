<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ThemeController;


Route::view('/', 'welcome')->name('home');

Route::resource('themes', ThemeController::class);
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});
    
require __DIR__.'/settings.php';
