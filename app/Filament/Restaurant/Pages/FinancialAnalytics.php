<?php

namespace App\Filament\Restaurant\Pages;

use App\Filament\Restaurant\Widgets\FinancialOverviewWidget;
use App\Filament\Restaurant\Widgets\OrderTypeChartWidget;
use App\Filament\Restaurant\Widgets\PaymentMethodChartWidget;
use App\Filament\Restaurant\Widgets\SalesChartWidget;
use App\Filament\Restaurant\Widgets\TopProductsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class FinancialAnalytics extends Page
{
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

    protected function getHeaderWidgets(): array
    {
        return [
            FinancialOverviewWidget::class,
            TopProductsWidget::class,
            OrderTypeChartWidget::class,
            SalesChartWidget::class,
            PaymentMethodChartWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'lg' => 3];
    }
}
