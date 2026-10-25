<?php

namespace App\Filament\Restaurant\Widgets;

use App\Models\Order_item;
use App\Services\FinancialAnalyticsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class TopProductsWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = ['md' => 2, 'lg' => 2];

    public function table(Table $table): Table
    {
        $restaurant = Auth::user()?->restaurant;
        $analytics = app(FinancialAnalyticsService::class);
        $period = $analytics->period($this->pageFilters);
        $currency = $restaurant?->currency_symbol ?: 'ل.س';

        $query = Order_item::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $restaurant?->id)
            ->where('orders.payment_status', 'verified')
            ->where('orders.status', '!=', 'cancelled')
            ->whereNull('orders.deleted_at')
            ->whereNull('order_items.deleted_at')
            ->whereBetween('orders.created_at', [$period['start'], $period['end']])
            ->selectRaw('MIN(order_items.id) as id, order_items.product_id as product_id, order_items.product_name as product_name, order_items.variant_name as variant_name, SUM(order_items.quantity) as quantity_sold, SUM(order_items.total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.variant_name')
            ->orderByDesc('quantity_sold')
            ->orderByRaw('MIN(order_items.id) ASC');

        return $table
            ->heading('أداء المنتجات خلال الفترة المحددة')
            ->query($query)
            ->defaultSort('quantity_sold', 'desc')
            ->defaultKeySort(false)
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('product_name')
                    ->label('المنتج')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Order_item $record): ?string => filled($record->product_id)
                        ? $analytics->ordersReportUrl($period, [
                            'verified_sales' => true,
                            'product_id' => $record->product_id,
                        ])
                        : null)
                    ->wrap(),
                TextColumn::make('variant_name')
                    ->label('الخيار')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('quantity_sold')
                    ->label('الكمية المباعة')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('revenue')
                    ->label('قيمة الأصناف')
                    ->description('قبل توزيع خصومات الطلب على الأصناف.')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2).' '.$currency)
                    ->sortable(),
            ]);
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
