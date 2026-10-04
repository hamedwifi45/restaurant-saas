<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('restaurant.{restaurantId}.kds', function (User $user, int $restaurantId) {
    return in_array($user->role, ['restaurant_admin', 'kitchen_staff'], true)
        && (int) $user->restaurant_id === $restaurantId;
});

// 2. قناة المندوبين الخاصة بالمطعم (Drivers)
Broadcast::channel('restaurant.{restaurantId}.drivers', function (User $user, int $restaurantId) {
    // يطابق صلاحية المندوب ورقم مطعمه
    return $user->role === 'driver' && (int) $user->restaurant_id === (int) $restaurantId;
});

Broadcast::channel('restaurant.{restaurantId}.admin', function (User $user, int $restaurantId): bool {
    $isSuperAdmin = $user->role === 'super_admin' || $user->hasRole('super_admin');
    $isRestaurantManager = in_array($user->role, ['restaurant_admin', 'restaurant_owner'], true)
        || $user->hasRole('restaurant_admin')
        || $user->hasRole('restaurant_owner');
    $isKitchenStaff = $user->role === 'kitchen_staff' || $user->hasRole('kitchen_staff');

    return $isSuperAdmin
        || (($isRestaurantManager || $isKitchenStaff)
            && (int) $user->restaurant_id === $restaurantId);
});
