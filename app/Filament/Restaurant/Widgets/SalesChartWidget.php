<?php

namespace App\Filament\Restaurant\Widgets;

use App\Models\Order;
use App\Models\Restaurant;
use App\Services\FinancialAnalyticsService;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SalesChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'اتجاه صافي مبيعات المنتجات مقابل الفترة السابقة';

    protected int|string|array $columnSpan = ['md' => 2, 'lg' => 2];

    protected function getType(): string
    {
        return 'line';
    }

    public function getDescription(): HtmlString
    {
        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $url = e($analytics->ordersReportUrl($period, ['verified_sales' => true]));

        return new HtmlString('<a class="text-primary-600 underline dark:text-primary-400" href="'.$url.'">عرض الطلبات التي تستند إليها المبيعات</a>');
    }

    protected function getData(): array
    {
        $restaurant = Auth::user()?->restaurant;

        if (! $restaurant) {
            return ['datasets' => [['label' => 'Revenue', 'data' => []]], 'labels' => []];
        }

        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $previousPeriod = [
            ...$period,
            'start' => $period['previous_start'],
            'end' => $period['previous_end'],
        ];
        $currentSales = $this->salesByDate($analytics, $restaurant, $period);
        $previousSales = $this->salesByDate($analytics, $restaurant, $previousPeriod);
        $days = range(0, $period['days'] - 1);

        return [
            'datasets' => [[
                'label' => 'صافي المبيعات - الفترة الحالية',
                'data' => array_map(fn (int $offset): float => (float) ($currentSales[$offset] ?? 0), $days),
            ], [
                'label' => 'صافي المبيعات - الفترة السابقة المماثلة',
                'data' => array_map(fn (int $offset): float => (float) ($previousSales[$offset] ?? 0), $days),
            ]],
            'labels' => array_map(
                fn (int $offset): string => $period['start']->addDays($offset)->format('d/m'),
                $days,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $period
     * @return array<int, float>
     */
    private function salesByDate(FinancialAnalyticsService $analytics, Restaurant $restaurant, array $period): array
    {
        return $analytics->ordersForPeriod($restaurant, $period)
            ->selectRaw('DATE(created_at) as order_date, SUM(subtotal - discount - coupon_discount) as net_sales')
            ->groupBy('order_date')
            ->get()
            ->mapWithKeys(fn (Order $order): array => [
                CarbonImmutable::parse($order->order_date)->diffInDays($period['start']) => (float) $order->net_sales,
            ])
            ->all();
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
