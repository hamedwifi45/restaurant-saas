<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isRestaurantScoped($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->isRestaurantScoped($user) && $product->restaurant_id === $user->restaurant_id;
    }

    public function create(User $user): bool
    {
        return $this->isRestaurantScoped($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->isRestaurantScoped($user) && $product->restaurant_id === $user->restaurant_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->isRestaurantScoped($user) && $product->restaurant_id === $user->restaurant_id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $this->isRestaurantScoped($user) && $product->restaurant_id === $user->restaurant_id;
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $this->isRestaurantScoped($user) && $product->restaurant_id === $user->restaurant_id;
    }

    protected function isRestaurantScoped(User $user): bool
    {
        return isset($user->restaurant_id) && $user->restaurant_id !== null;
    }
}
