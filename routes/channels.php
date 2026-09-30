<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('restaurant.{restaurantId}.kds', function (User $user, int $restaurantId) {
    return in_array($user->role, ['restaurant_admin', 'kitchen_staff']) 
        && (int) $user->restaurant_id === (int) $restaurantId;
});

// 2. قناة المندوبين الخاصة بالمطعم (Drivers)
Broadcast::channel('restaurant.{restaurantId}.drivers', function (User $user, int $restaurantId) {
    // يطابق صلاحية المندوب ورقم مطعمه
    return $user->role === 'driver' && (int) $user->restaurant_id === (int) $restaurantId;
});