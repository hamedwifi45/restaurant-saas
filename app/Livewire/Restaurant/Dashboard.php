<?php

namespace App\Livewire\Restaurant;

use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('لوحة التحكم')]
class Dashboard extends Component
{
    public function render()
    {
        $restaurant = Auth::user()->restaurant;
        
        if (!$restaurant) {
            return view('livewire.restaurant.no-restaurant');
        }

        // إحصائيات سريعة
        $todayOrders = Order::where('restaurant_id', $restaurant->id)
            ->whereDate('created_at', today())
            ->count();
        
        $pendingOrders = Order::where('restaurant_id', $restaurant->id)
            ->where('status', 'pending')
            ->count();
        
        $totalProducts = Product::where('restaurant_id', $restaurant->id)->count();
        
        $totalCategories = Category::where('restaurant_id', $restaurant->id)->count();
        
        $recentOrders = Order::where('restaurant_id', $restaurant->id)
            ->with('items.product')
            ->latest()
            ->limit(5)
            ->get();
        
        $revenue = Order::where('restaurant_id', $restaurant->id)
            ->where('status', 'completed')
            ->sum('total');
        
        $avgRating = $restaurant->reviews()->avg('rating') ?? 0;
        $totalReviews = $restaurant->reviews()->count();

        return view('livewire.restaurant.dashboard', [
            'restaurant' => $restaurant,
            'todayOrders' => $todayOrders,
            'pendingOrders' => $pendingOrders,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'recentOrders' => $recentOrders,
            'revenue' => $revenue,
            'avgRating' => round($avgRating, 1),
            'totalReviews' => $totalReviews,
        ]);
    }
}
