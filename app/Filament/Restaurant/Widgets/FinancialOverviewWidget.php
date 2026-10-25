<?php

namespace App\Filament\Restaurant\Widgets;

use App\Services\FinancialAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinancialOverviewWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = ['default' => 1, 'md' => 2, 'xl' => 4];

    protected function getStats(): array
    {
        $restaurant = Auth::user()?->restaurant;

        if (! $restaurant) {
            return [];
        }

        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $current = $analytics->metrics($restaurant, $period);
        $previous = $analytics->metrics($restaurant, [
            ...$period,
            'start' => $period['previous_start'],
            'end' => $period['previous_end'],
        ]);
        $currency = $restaurant->currency_symbol ?: 'ل.س';

        return [
            Stat::make('إجمالي مبيعات الطلبات', $this->formatAmount($current['gross_sales'], $currency))
                ->description($this->comparison($current['gross_sales'], $previous['gross_sales'], $currency, 'قيمة المنتجات قبل الخصومات، للطلبات ذات الدفع المؤكد.'))
                ->url($analytics->ordersReportUrl($period, ['verified_sales' => true])),
            Stat::make('الخصومات والكوبونات', $this->formatAmount($current['discounts'], $currency))
                ->description($this->comparison($current['discounts'], $previous['discounts'], $currency, 'خصومات مسجلة على الطلبات ذات الدفع المؤكد.'))
                ->url($analytics->ordersReportUrl($period, ['verified_sales' => true])),
            Stat::make('صافي المبيعات', $this->formatAmount($current['net_sales'], $currency))
                ->description($this->comparison($current['net_sales'], $previous['net_sales'], $currency, 'مبيعات المنتجات ناقص الخصومات؛ لا تشمل الضرائب أو التوصيل أو المصاريف أو تكلفة المنتجات.'))
                ->url($analytics->ordersReportUrl($period, ['verified_sales' => true])),
            Stat::make('طلبات بدفع مؤكد', number_format($current['paid_order_count']))
                ->description($this->comparison($current['paid_order_count'], $previous['paid_order_count'], '', 'عدد الطلبات المدفوعة بحسب حالة التحقق المسجلة.'))
                ->url($analytics->ordersReportUrl($period, ['verified_sales' => true])),
            Stat::make('متوسط قيمة الطلب المؤكد', $this->formatAmount($current['average_paid_order_value'], $currency))
                ->description($this->comparison($current['average_paid_order_value'], $previous['average_paid_order_value'], $currency, 'متوسط final_amount للطلبات المؤكدة؛ يشمل الضريبة والتوصيل.'))
                ->url($analytics->ordersReportUrl($period, ['verified_sales' => true])),
            Stat::make('المصاريف المسجلة', $this->formatAmount($current['recorded_expenses'], $currency))
                ->description($this->comparison($current['recorded_expenses'], $previous['recorded_expenses'], $currency, 'حسب تاريخ الصرف؛ لا يمثل بالضرورة كل تكاليف المطعم.'))
                ->url($analytics->expensesReportUrl($period)),
            Stat::make('الطلبات الملغاة', number_format($current['cancelled_order_count']))
                ->description($this->comparison($current['cancelled_order_count'], $previous['cancelled_order_count'], '', 'معدل الإلغاء: '.$this->formatPercent($current['cancellation_rate']).'؛ من جميع الطلبات المسجلة في الفترة.'))
                ->url($analytics->ordersReportUrl($period, ['status' => 'cancelled'])),
            Stat::make('فواتير بحالة مستردة', number_format($current['refunded_invoice_order_count']))
                ->description('فواتير مرتبطة بطلبات في الفترة وحالتها الحالية «مستردة»؛ لا تتوفر قيمة أو تاريخ معاملة الاسترداد.')
                ->url($analytics->ordersReportUrl($period, ['invoice_status' => 'refunded'])),
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }

    private function formatAmount(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }

    private function comparison(float|int $current, float|int $previous, string $currency, string $definition): string
    {
        $difference = $current - $previous;
        $change = $previous == 0
            ? 'لا توجد قيمة سابقة للمقارنة'
            : 'التغير '.$this->formatSignedAmount($difference, $currency)
                .' ('.number_format(($difference / abs($previous)) * 100, 1).'%)';

        return $definition.' '.$change;
    }

    private function formatSignedAmount(float|int $amount, string $currency): string
    {
        $sign = $amount > 0 ? '+' : '';

        return $sign.number_format($amount, 2).' '.$currency;
    }

    private function formatPercent(float $rate): string
    {
        return number_format($rate * 100, 1).'%';
    }
}
