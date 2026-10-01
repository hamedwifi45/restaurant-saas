<?php

namespace App\Filament\Restaurant\Resources\RestaurantTables;

use App\Filament\Restaurant\Resources\RestaurantTables\Pages\CreateRestaurantTable;
use App\Filament\Restaurant\Resources\RestaurantTables\Pages\EditRestaurantTable;
use App\Filament\Restaurant\Resources\RestaurantTables\Pages\ListRestaurantTables;
use App\Filament\Restaurant\Resources\RestaurantTables\Schemas\RestaurantTableForm;
use App\Filament\Restaurant\Resources\RestaurantTables\Tables\RestaurantTablesTable;
use App\Models\RestaurantTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RestaurantTableResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;

    protected static ?string $navigationLabel = 'الطاولات و QR';

    protected static ?string $modelLabel = 'طاولة';

    protected static ?string $pluralModelLabel = 'الطاولات و QR';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return RestaurantTableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantTablesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('restaurant_id', Auth::user()?->restaurant_id);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->restaurant?->hasFeature('table_qr') ?? false;
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
            'index' => ListRestaurantTables::route('/'),
            'create' => CreateRestaurantTable::route('/create'),
            'edit' => EditRestaurantTable::route('/{record}/edit'),
        ];
    }
}
