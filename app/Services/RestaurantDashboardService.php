<?php

namespace App\Services;

use App\Models\Restaurant;
use Illuminate\Support\Collection;

class RestaurantDashboardService
{
    /**
     * @return array{success: bool, summary: array{sales_today: float, active_orders: int, customers_today: int, average_order: float}, recent_orders: array<int, array<string, mixed>>, updated_at: string}
     */
    public function forRestaurant(?Restaurant $restaurant): array
    {
        if (! $restaurant) {
            return $this->emptyDashboard();
        }

        $orders = $restaurant->orders()->whereNull('deleted_at');
        $todayOrders = (clone $orders)->whereDate('created_at', today());
        $salesEligibleToday = (clone $todayOrders)->verifiedNonCancelled();
        $salesToday = (float) (clone $salesEligibleToday)->sum('final_amount');
        $todayOrderCount = (clone $salesEligibleToday)->count();

        return [
            'success' => true,
            'summary' => [
                'sales_today' => $salesToday,
                'active_orders' => (clone $orders)->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])->count(),
                'customers_today' => (int) (clone $todayOrders)
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('customer_phone')
                    ->distinct()
                    ->count('customer_phone'),
                'average_order' => $todayOrderCount > 0 ? $salesToday / $todayOrderCount : 0,
            ],
            'recent_orders' => $this->recentOrders($orders->withCount('items')->latest()->take(6)->get()),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    private function recentOrders(Collection $orders): array
    {
        return $orders->map(fn ($order): array => [
            'id' => '#'.$order->id,
            'customer' => $order->customer_name ?: 'عميل غير مسجل',
            'type' => match ($order->delivery_type) {
                'takeaway' => 'استلام من المطعم',
                'dine_in' => 'داخل المطعم',
                default => 'توصيل',
            },
            'items' => (int) $order->items_count,
            'total' => (float) ($order->final_amount ?? 0),
            'status' => $order->status,
            'time' => $order->created_at?->diffForHumans() ?? 'غير معروف',
        ])->all();
    }

    /**
     * @return array{success: bool, summary: array{sales_today: float, active_orders: int, customers_today: int, average_order: float}, recent_orders: array<int, array<string, mixed>>, updated_at: string}
     */
    private function emptyDashboard(): array
    {
        return [
            'success' => false,
            'summary' => [
                'sales_today' => 0,
                'active_orders' => 0,
                'customers_today' => 0,
                'average_order' => 0,
            ],
            'recent_orders' => [],
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
