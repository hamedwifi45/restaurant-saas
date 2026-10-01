<?php

namespace App\Filament\Restaurant\Widgets;

use App\Models\Order_item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class TopProductsWidget extends TableWidget
{
    protected int|string|array $columnSpan = ['md' => 2, 'lg' => 2];

    public function table(Table $table): Table
    {
        $restaurantId = Auth::user()?->restaurant_id;

        $query = Order_item::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->where('orders.payment_status', 'verified')
            ->where('orders.status', '!=', 'cancelled')
            ->whereBetween('orders.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('MIN(order_items.id) as id, order_items.product_name as product_name, SUM(order_items.quantity) as quantity_sold, SUM(order_items.total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('quantity_sold')
            ->orderByRaw('MIN(order_items.id) ASC')
            ->limit(5);

        return $table
            ->heading('أفضل المنتجات مبيعًا هذا الشهر')
            ->query($query)
            ->defaultSort('quantity_sold', 'desc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('product_name')
                    ->label('المنتج')
                    ->wrap(),
                TextColumn::make('quantity_sold')
                    ->label('الكمية المباعة')
                    ->numeric(),
                TextColumn::make('revenue')
                    ->label('إجمالي المبيعات')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2).' ل.س'),
            ]);
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin'
            && ($user->restaurant?->hasFeature('advanced_financial_analytics') ?? false);
    }
}
