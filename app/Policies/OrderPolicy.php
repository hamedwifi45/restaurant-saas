<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isRestaurantScoped($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isRestaurantScoped($user) && $order->restaurant_id === $user->restaurant_id;
    }

    public function create(User $user): bool
    {
        return $this->isRestaurantScoped($user);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->isRestaurantScoped($user) && $order->restaurant_id === $user->restaurant_id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->isRestaurantScoped($user) && $order->restaurant_id === $user->restaurant_id;
    }

    public function restore(User $user, Order $order): bool
    {
        return $this->isRestaurantScoped($user) && $order->restaurant_id === $user->restaurant_id;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $this->isRestaurantScoped($user) && $order->restaurant_id === $user->restaurant_id;
    }

    protected function isRestaurantScoped(User $user): bool
    {
        return isset($user->restaurant_id) && $user->restaurant_id !== null;
    }
}
