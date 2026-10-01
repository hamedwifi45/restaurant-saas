<?php

namespace App\Filament\Restaurant\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PaymentMethodChartWidget extends ChartWidget
{
    protected ?string $heading = 'المبيعات حسب طريقة الدفع';

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
                'labels' => ['نقدًا', 'تحويل مصرفي / إلكتروني'],
            ];
        }

        $revenueByMethod = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('payment_status', 'verified')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereIn('payment_method', ['cash', 'transfer', 'bank_transfer'])
            ->selectRaw("CASE WHEN payment_method = 'cash' THEN 'cash' ELSE 'bank_transfer' END as payment_method, SUM(final_amount) as revenue")
            ->groupByRaw("CASE WHEN payment_method = 'cash' THEN 'cash' ELSE 'bank_transfer' END")
            ->pluck('revenue', 'payment_method');

        return [
            'datasets' => [[
                'label' => 'المبيعات',
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
