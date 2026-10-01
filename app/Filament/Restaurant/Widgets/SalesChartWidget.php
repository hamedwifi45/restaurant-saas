<?php

namespace App\Filament\Restaurant\Widgets;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SalesChartWidget extends ChartWidget
{
    protected ?string $heading = 'اتجاه المبيعات اليومي لهذا الشهر';

    protected int|string|array $columnSpan = ['md' => 2, 'lg' => 2];

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $restaurantId = Auth::user()?->restaurant_id;

        if (! $restaurantId) {
            return ['datasets' => [['label' => 'Revenue', 'data' => []]], 'labels' => []];
        }

        $month = CarbonImmutable::now();
        $daysInMonth = $month->daysInMonth;
        $revenueByDay = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('payment_status', 'verified')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->selectRaw('DATE(created_at) as order_date, SUM(final_amount) as revenue')
            ->groupBy('order_date')
            ->get()
            ->mapWithKeys(fn (Order $order): array => [
                CarbonImmutable::parse($order->order_date)->day => (float) $order->revenue,
            ]);
        $days = range(1, $daysInMonth);

        return [
            'datasets' => [[
                'label' => 'المبيعات اليومية',
                'data' => array_map(fn (int $day): float => (float) ($revenueByDay[$day] ?? 0), $days),
            ]],
            'labels' => array_map(static fn (int $day): string => (string) $day, $days),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
