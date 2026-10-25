<?php

namespace App\Filament\Restaurant\Resources\Orders;

use App\Filament\Restaurant\Resources\Orders\Pages\CreateOrder;
use App\Filament\Restaurant\Resources\Orders\Pages\EditOrder;
use App\Filament\Restaurant\Resources\Orders\Pages\ListOrders;
use App\Filament\Restaurant\Resources\Orders\Schemas\OrderForm;
use App\Filament\Restaurant\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Models\User;
use App\Policies\OrderPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $modelPolicy = OrderPolicy::class;

    protected static ?string $navigationLabel = 'الطلبات';

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    protected static ?string $recordTitleAttribute = 'tracking_code';

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('deliveryDispatch.provider', 'deliveryDispatch.events.actor');

        $user = auth('web')->user();

        if ($user instanceof User && filled($user->restaurant_id)) {
            $query->where('restaurant_id', $user->restaurant_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
