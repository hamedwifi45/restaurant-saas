<?php

namespace App\Filament\Restaurant\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OrderTypeChartWidget extends ChartWidget
{
    protected ?string $heading = 'المبيعات حسب نوع الطلب';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $restaurantId = Auth::user()?->restaurant_id;

        if (! $restaurantId) {
            return [
                'datasets' => [['label' => 'المبيعات', 'data' => [0, 0]]],
                'labels' => ['التوصيل', 'طلبات الطاولات'],
            ];
        }

        $revenueByType = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('payment_status', 'verified')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereIn('delivery_type', ['delivery', 'dine_in'])
            ->selectRaw('delivery_type, SUM(final_amount) as revenue')
            ->groupBy('delivery_type')
            ->pluck('revenue', 'delivery_type');

        return [
            'datasets' => [[
                'label' => 'المبيعات',
                'data' => [
                    (float) ($revenueByType['delivery'] ?? 0),
                    (float) ($revenueByType['dine_in'] ?? 0),
                ],
            ]],
            'labels' => ['التوصيل', 'طلبات الطاولات'],
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
