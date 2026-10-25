<?php

namespace App\Filament\Restaurant\Widgets;

use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class OrderDemandPatternWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    public function getHeading(): ?string
    {
        return ($this->pageFilters['pattern'] ?? 'hour') === 'weekday'
            ? 'توزيع الطلبات حسب يوم الأسبوع'
            : 'توزيع الطلبات حسب ساعة الإنشاء';
    }

    public function getDescription(): HtmlString
    {
        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $url = e($analytics->ordersReportUrl($period, ['non_cancelled_only' => true]));

        return new HtmlString('<a class="text-primary-600 underline dark:text-primary-400" href="'.$url.'">عرض الطلبات غير الملغاة في هذه الفترة</a>');
    }

    protected function getData(): array
    {
        $restaurant = Auth::user()?->restaurant;

        if (! $restaurant) {
            return [
                'datasets' => [['label' => 'الطلبات غير الملغاة', 'data' => []]],
                'labels' => [],
            ];
        }

        $pattern = ($this->pageFilters['pattern'] ?? 'hour') === 'weekday'
            ? 'weekday'
            : 'hour';
        $period = app(FinancialAnalyticsService::class)->period($this->pageFilters);
        $counts = app(FinancialAnalyticsService::class)->orderCountsByPattern($restaurant, $period, $pattern);
        $labels = $pattern === 'weekday'
            ? ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد']
            : array_map(static fn (int $hour): string => sprintf('%02d:00', $hour), range(0, 23));

        return [
            'datasets' => [[
                'label' => 'الطلبات غير الملغاة',
                'data' => array_map(
                    fn (int $bucket): int => $counts[$bucket] ?? 0,
                    range(0, count($labels) - 1),
                ),
            ]],
            'labels' => $labels,
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
