<?php

namespace App\Filament\Restaurant\Resources\Staff;

use App\Filament\Restaurant\Resources\Staff\Pages\CreateStaff;
use App\Filament\Restaurant\Resources\Staff\Pages\EditStaff;
use App\Filament\Restaurant\Resources\Staff\Pages\ListStaff;
use App\Filament\Restaurant\Resources\Staff\Schemas\StaffForm;
use App\Filament\Restaurant\Resources\Staff\Tables\StaffTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'فريق العمل والموظفين';

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المطعم';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'موظف';

    protected static ?string $pluralModelLabel = 'فريق العمل والموظفين';

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return filled(auth()->user()?->restaurant_id);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->whereIn('role', ['kitchen_staff', 'driver', 'cashier', 'restaurant_admin']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
