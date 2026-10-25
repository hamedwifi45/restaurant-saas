<?php

namespace App\Filament\Restaurant\Widgets;

use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class OrderTypeChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'قيمة الطلبات ذات الدفع المؤكد حسب النوع';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getDescription(): HtmlString
    {
        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $url = e($analytics->ordersReportUrl($period, ['verified_sales' => true]));

        return new HtmlString('<a class="text-primary-600 underline dark:text-primary-400" href="'.$url.'">عرض الطلبات المندرجة ضمن هذا التقرير</a>');
    }

    protected function getData(): array
    {
        $restaurant = Auth::user()?->restaurant;

        if (! $restaurant) {
            return [
                'datasets' => [['label' => 'قيمة الطلبات', 'data' => [0, 0, 0]]],
                'labels' => ['التوصيل', 'الاستلام', 'داخل المطعم'],
            ];
        }

        $period = app(FinancialAnalyticsService::class)->period($this->pageFilters);
        $revenueByType = app(FinancialAnalyticsService::class)->ordersForPeriod($restaurant, $period)
            ->whereIn('delivery_type', ['delivery', 'takeaway', 'dine_in'])
            ->selectRaw('delivery_type, SUM(final_amount) as order_value')
            ->groupBy('delivery_type')
            ->pluck('order_value', 'delivery_type');

        return [
            'datasets' => [[
                'label' => 'قيمة الطلبات',
                'data' => [
                    (float) ($revenueByType['delivery'] ?? 0),
                    (float) ($revenueByType['takeaway'] ?? 0),
                    (float) ($revenueByType['dine_in'] ?? 0),
                ],
            ]],
            'labels' => ['التوصيل', 'الاستلام', 'داخل المطعم'],
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
