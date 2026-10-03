<?php

namespace App\Support;

use App\Models\User;

class AuthenticatedUserRedirect
{
    public static function for(?User $user): string
    {
        if (! $user) {
            return route('home');
        }

        if ($user->restaurant && $user->canAccessDriverPanelFor($user->restaurant)) {
            return route('driver.orders', ['slug' => $user->restaurant->slug]);
        }

        return match ($user->role) {
            'kitchen_staff' => $user->restaurant
                ? route('kitchen.display', ['slug' => $user->restaurant->slug])
                : route('home'),
            'restaurant_admin' => url('/restaurant'),
            'super_admin' => url('/sham'),
            default => route('home'),
        };
    }
}
