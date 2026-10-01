<?php

namespace App\Filament\Restaurant\Resources\Drivers;

use App\Filament\Restaurant\Resources\Drivers\Pages\CreateDriver;
use App\Filament\Restaurant\Resources\Drivers\Pages\EditDriver;
use App\Filament\Restaurant\Resources\Drivers\Pages\ListDrivers;
use App\Filament\Restaurant\Resources\Drivers\Schemas\DriverForm;
use App\Filament\Restaurant\Resources\Drivers\Tables\DriversTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class DriverResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'مندوب التوصيل';

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'مندوب';

    protected static ?string $pluralModelLabel = 'مندوبو التوصيل';

    public static function form(Schema $schema): Schema
    {
        return DriverForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DriversTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'driver')
            ->where('restaurant_id', Auth::user()?->restaurant_id);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->restaurant?->hasFeature('gps_tracking') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDrivers::route('/'),
            'create' => CreateDriver::route('/create'),
            'edit' => EditDriver::route('/{record}/edit'),
        ];
    }
}
