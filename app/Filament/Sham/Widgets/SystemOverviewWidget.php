<?php

namespace App\Filament\Sham\Widgets;

use App\Models\Order;
use App\Models\Restaurant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $restaurants = Restaurant::query();
        $totalRestaurants = (clone $restaurants)->count();
        $starterCount = (clone $restaurants)->where('plan', 'starter')->count();
        $proCount = (clone $restaurants)->where('plan', 'pro')->count();
        $activeCount = (clone $restaurants)->where('is_active', true)->count();
        $suspendedCount = (clone $restaurants)->where('is_active', false)->count();
        $grossRevenue = (float) Order::query()
            ->where('payment_status', 'verified')
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        $subscriptionRevenue = (float) (clone $restaurants)
            ->where('is_active', true)
            ->where('pricing_type', 'subscription')
            ->sum('subscription_fee');

        return [
            Stat::make('إجمالي المطاعم', $totalRestaurants)
                ->description("Starter: {$starterCount} | Pro: {$proCount}"),
            Stat::make('الحسابات النشطة', $activeCount),
            Stat::make('الحسابات الموقوفة', $suspendedCount),
            Stat::make('إجمالي مبيعات المنصة', $this->formatAmount($grossRevenue)),
            Stat::make('إيرادات الاشتراكات الشهرية المقدرة', $this->formatAmount($subscriptionRevenue)),
        ];
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2).' ل.س';
    }
}
