<?php

namespace App\Filament\Restaurant\Widgets;

use App\Models\Expense;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinancialOverviewWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $restaurantId = Auth::user()?->restaurant_id;

        if (! $restaurantId) {
            return [];
        }

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $orders = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('payment_status', 'verified')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        $grossRevenue = (float) (clone $orders)
            ->selectRaw('COALESCE(SUM(subtotal + tax_amount + delivery_fee), 0) as gross_revenue')
            ->value('gross_revenue');
        $totalDiscounts = (float) (clone $orders)
            ->selectRaw('COALESCE(SUM(discount + coupon_discount), 0) as total_discounts')
            ->value('total_discounts');
        $totalExpenses = (float) Expense::query()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('spent_at', '>=', $startOfMonth->toDateString())
            ->whereDate('spent_at', '<=', $endOfMonth->toDateString())
            ->sum('amount');
        $netProfit = $grossRevenue - $totalExpenses - $totalDiscounts;
        $averageOrderValue = (float) (clone $orders)->avg('final_amount');

        return [
            Stat::make('إجمالي المبيعات', $this->formatAmount($grossRevenue)),
            Stat::make('صافي الأرباح', $this->formatAmount($netProfit)),
            Stat::make('المصاريف', $this->formatAmount($totalExpenses)),
            Stat::make('متوسط الطلب', $this->formatAmount($averageOrderValue)),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2).' ل.س';
    }
}
