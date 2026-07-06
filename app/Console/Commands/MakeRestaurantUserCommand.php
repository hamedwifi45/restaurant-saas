<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeRestaurantUserCommand extends Command
{
    protected $signature = 'make:restaurant-user';

    protected $description = 'Create a restaurant admin user and attach them to a restaurant';

    public function handle(): int
{
    $name = $this->ask('Name');
    $email = $this->ask('Email');
    
    if(User::where('email', $email)->exists()) {
        $this->error('A user with this email already exists.');
        return self::FAILURE;
    }
    
    $password = $this->secret('Password');

    // جلب المطاعم النشطة
    $restaurants = Restaurant::query()
        ->where('is_active', true)
        ->get(['id', 'name']);

    if ($restaurants->isEmpty()) {
        $this->error('No active restaurants found.');
        return self::FAILURE;
    }

    // بناء قائمة تعرض: "ID - Name" لتجنب الالتباس
    $options = $restaurants->map(fn ($r) => "{$r->id} - {$r->name}")->toArray();

    // المستخدم يختار من القائمة
    $selected = $this->choice('Select restaurant', $options);

    // استخراج الـ ID من النص المختار (الجزء قبل الـ " - ")
    $restaurantId = (int) explode(' - ', $selected)[0];

    // التحقق من وجود المطعم
    $restaurant = $restaurants->firstWhere('id', $restaurantId);
    
    if (!$restaurant) {
        $this->error('Selected restaurant not found.');
        return self::FAILURE;
    }

    // إنشاء المستخدم مع تعيين الدور والمطعم
    User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'restaurant_id' => $restaurantId,
        'role' => 'restaurant_admin',
    ]);

    $this->info("✅ Restaurant admin created successfully!");
    $this->info("Name: {$name}");
    $this->info("Email: {$email}");
    $this->info("Restaurant: {$restaurant->name} (ID: {$restaurantId})");
    $this->info("Role: restaurant_admin");
    $this->info("Login at: http://localhost:8000/restaurant/login");

    return self::SUCCESS;
}
}