<?php

namespace App\Filament\Restaurant\Widgets;

use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PaymentMethodChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'قيمة الطلبات بحسب طريقة الدفع المسجلة';

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getDescription(): HtmlString
    {
        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $url = e($analytics->ordersReportUrl($period, ['verified_sales' => true]));

        return new HtmlString('<a class="text-primary-600 underline dark:text-primary-400" href="'.$url.'">عرض الطلبات ذات طرق الدفع المسجلة هذه</a>');
    }

    protected function getData(): array
    {
        $restaurant = Auth::user()?->restaurant;

        if (! $restaurant) {
            return [
                'datasets' => [['label' => 'قيمة الطلبات', 'data' => [0, 0]]],
                'labels' => ['نقدًا', 'تحويل مصرفي / إلكتروني'],
            ];
        }

        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $revenueByMethod = $analytics->ordersForPeriod($restaurant, $period)
            ->whereIn('payment_method', ['cash', 'transfer', 'bank_transfer'])
            ->selectRaw("CASE WHEN payment_method = 'cash' THEN 'cash' ELSE 'bank_transfer' END as payment_method, SUM(final_amount) as order_value")
            ->groupByRaw("CASE WHEN payment_method = 'cash' THEN 'cash' ELSE 'bank_transfer' END")
            ->pluck('order_value', 'payment_method');

        return [
            'datasets' => [[
                'label' => 'قيمة الطلبات',
                'data' => [
                    (float) ($revenueByMethod['cash'] ?? 0),
                    (float) ($revenueByMethod['bank_transfer'] ?? 0),
                ],
            ]],
            'labels' => ['نقدًا', 'تحويل مصرفي / إلكتروني'],
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
