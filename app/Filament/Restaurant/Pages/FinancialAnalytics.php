<?php

namespace App\Filament\Restaurant\Pages;

use App\Filament\Restaurant\Widgets\FinancialOverviewWidget;
use App\Filament\Restaurant\Widgets\OrderDemandPatternWidget;
use App\Filament\Restaurant\Widgets\OrderTypeChartWidget;
use App\Filament\Restaurant\Widgets\PaymentMethodChartWidget;
use App\Filament\Restaurant\Widgets\SalesChartWidget;
use App\Filament\Restaurant\Widgets\TopProductsWidget;
use App\Services\FinancialAnalyticsService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class FinancialAnalytics extends Page
{
    public string $view = 'filament.pages.financial-analytics';

    public array $filters = [];

    protected static ?string $title = 'التقارير والتحليلات المالية';

    protected static ?string $navigationLabel = 'التقارير والتحليلات المالية';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'التقارير والمالية';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }

    public function mount(FinancialAnalyticsService $analytics): void
    {
        $period = $analytics->period($this->filters);
        $this->filters = [
            'startDate' => $period['start']->toDateString(),
            'endDate' => $period['end']->toDateString(),
        ];
    }

    public function setPeriodPreset(string $preset): void
    {
        $today = Carbon::today();

        [$start, $end] = match ($preset) {
            'today' => [$today, $today],
            '7_days' => [$today->copy()->subDays(6), $today],
            '30_days' => [$today->copy()->subDays(29), $today],
            'this_month' => [$today->copy()->startOfMonth(), $today],
            'previous_month' => [
                $today->copy()->subMonth()->startOfMonth(),
                $today->copy()->subMonth()->endOfMonth(),
            ],
            default => abort(404),
        };

        $this->filters = [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
        ];
    }

    public function setSalesPattern(string $pattern): void
    {
        abort_unless(in_array($pattern, ['hour', 'weekday'], true), 404);

        $this->filters['pattern'] = $pattern;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinancialOverviewWidget::class,
            TopProductsWidget::class,
            OrderTypeChartWidget::class,
            OrderDemandPatternWidget::class,
            SalesChartWidget::class,
            PaymentMethodChartWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'lg' => 3];
    }
}
