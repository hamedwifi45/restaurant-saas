<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

class FinancialAnalyticsService
{
    /**
     * @param  array<string, mixed>|null  $filters
     * @return array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     previous_start: CarbonImmutable,
     *     previous_end: CarbonImmutable,
     *     days: int
     * }
     */
    public function period(?array $filters): array
    {
        $defaultStart = CarbonImmutable::now()->startOfMonth();
        $defaultEnd = CarbonImmutable::now()->endOfDay();

        $start = $this->date($filters['startDate'] ?? null) ?? $defaultStart;
        $end = $this->date($filters['endDate'] ?? null) ?? $defaultEnd;

        if ($start->greaterThan($end) || $start->diffInDays($end) >= 366) {
            $start = $defaultStart;
            $end = $defaultEnd;
        }

        $start = $start->startOfDay();
        $end = $end->endOfDay();
        $days = (int) $start->diffInDays($end->startOfDay()) + 1;

        return [
            'start' => $start,
            'end' => $end,
            'previous_start' => $start->subDays($days),
            'previous_end' => $start->subDay()->endOfDay(),
            'days' => $days,
        ];
    }

    /**
     * @param  array<string, mixed>  $period
     * @return Builder<Order>
     */
    public function ordersForPeriod(Restaurant $restaurant, array $period, bool $paidOnly = true): Builder
    {
        $query = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$period['start'], $period['end']]);

        if ($paidOnly) {
            $query->verifiedNonCancelled();
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $period
     * @return array{
     *     gross_sales: float,
     *     discounts: float,
     *     net_sales: float,
     *     paid_order_count: int,
     *     average_paid_order_value: float,
     *     order_count: int,
     *     cancelled_order_count: int,
     *     cancellation_rate: float,
     *     refunded_invoice_order_count: int,
     *     recorded_expenses: float
     * }
     */
    public function metrics(Restaurant $restaurant, array $period): array
    {
        $row = $this->ordersForPeriod($restaurant, $period, paidOnly: false)
            ->selectRaw(
                'COUNT(*) as order_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_order_count,
                SUM(CASE WHEN payment_status = ? AND status != ? THEN 1 ELSE 0 END) as paid_order_count,
                COALESCE(SUM(CASE WHEN payment_status = ? AND status != ? THEN subtotal ELSE 0 END), 0) as gross_sales,
                COALESCE(SUM(CASE WHEN payment_status = ? AND status != ? THEN discount + coupon_discount ELSE 0 END), 0) as discounts,
                COALESCE(AVG(CASE WHEN payment_status = ? AND status != ? THEN final_amount END), 0) as average_paid_order_value',
                [
                    'cancelled',
                    'verified',
                    'cancelled',
                    'verified',
                    'cancelled',
                    'verified',
                    'cancelled',
                    'verified',
                    'cancelled',
                ],
            )
            ->firstOrFail();

        $grossSales = (float) $row->gross_sales;
        $discounts = (float) $row->discounts;
        $orderCount = (int) $row->order_count;
        $cancelledOrderCount = (int) $row->cancelled_order_count;

        return [
            'gross_sales' => $grossSales,
            'discounts' => $discounts,
            'net_sales' => $grossSales - $discounts,
            'paid_order_count' => (int) $row->paid_order_count,
            'average_paid_order_value' => (float) $row->average_paid_order_value,
            'order_count' => $orderCount,
            'cancelled_order_count' => $cancelledOrderCount,
            'cancellation_rate' => $orderCount > 0 ? $cancelledOrderCount / $orderCount : 0.0,
            'refunded_invoice_order_count' => $this->ordersForPeriod($restaurant, $period, paidOnly: false)
                ->whereHas('invoice', fn (Builder $query): Builder => $query->where('status', 'refunded'))
                ->count(),
            'recorded_expenses' => (float) Expense::query()
                ->where('restaurant_id', $restaurant->id)
                ->whereDate('spent_at', '>=', $period['start']->toDateString())
                ->whereDate('spent_at', '<=', $period['end']->toDateString())
                ->sum('amount'),
        ];
    }

    /**
     * @param  array<string, mixed>  $period
     * @param  array<string, mixed>  $filters
     */
    public function ordersReportUrl(array $period, array $filters = []): string
    {
        return route('filament.restaurant.resources.orders.index', [
            'filters' => [
                'report_period' => [
                    'created_from' => $period['start']->toDateString(),
                    'created_until' => $period['end']->toDateString(),
                    ...$filters,
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $period
     */
    public function expensesReportUrl(array $period): string
    {
        return route('filament.restaurant.resources.expenses.index', [
            'filters' => [
                'report_period' => [
                    'spent_from' => $period['start']->toDateString(),
                    'spent_until' => $period['end']->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $period
     * @return array<int, int>
     */
    public function orderCountsByPattern(Restaurant $restaurant, array $period, string $pattern): array
    {
        if (! in_array($pattern, ['hour', 'weekday'], true)) {
            abort(404);
        }

        $driver = DB::connection()->getDriverName();
        $expressions = match ($driver) {
            'mysql', 'mariadb' => [
                'hour' => 'HOUR(created_at)',
                'weekday' => 'WEEKDAY(created_at)',
            ],
            'sqlite' => [
                'hour' => "CAST(strftime('%H', created_at) AS INTEGER)",
                'weekday' => "(CAST(strftime('%w', created_at) AS INTEGER) + 6) % 7",
            ],
            'pgsql' => [
                'hour' => 'CAST(EXTRACT(HOUR FROM created_at) AS INTEGER)',
                'weekday' => 'CAST(EXTRACT(ISODOW FROM created_at) AS INTEGER) - 1',
            ],
            default => throw new LogicException("Financial demand analytics do not support the {$driver} database driver."),
        };

        return $this->ordersForPeriod($restaurant, $period, paidOnly: false)
            ->where('status', '!=', 'cancelled')
            ->selectRaw($expressions[$pattern].' as bucket, COUNT(*) as order_count')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->mapWithKeys(fn (Order $order): array => [(int) $order->bucket => (int) $order->order_count])
            ->all();
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $matches)) {
            return null;
        }

        if (! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return null;
        }

        return CarbonImmutable::create((int) $matches[1], (int) $matches[2], (int) $matches[3]);
    }
}
