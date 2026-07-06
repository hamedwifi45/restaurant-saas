<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isRestaurantScoped($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->isRestaurantScoped($user) && $category->restaurant_id === $user->restaurant_id;
    }

    public function create(User $user): bool
    {
        return $this->isRestaurantScoped($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->isRestaurantScoped($user) && $category->restaurant_id === $user->restaurant_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->isRestaurantScoped($user) && $category->restaurant_id === $user->restaurant_id;
    }

    public function restore(User $user, Category $category): bool
    {
        return $this->isRestaurantScoped($user) && $category->restaurant_id === $user->restaurant_id;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $this->isRestaurantScoped($user) && $category->restaurant_id === $user->restaurant_id;
    }

    protected function isRestaurantScoped(User $user): bool
    {
        return isset($user->restaurant_id) && $user->restaurant_id !== null;
    }
}
