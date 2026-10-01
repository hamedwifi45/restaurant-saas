<?php

namespace App\Filament\Restaurant\Resources\Expenses;

use App\Filament\Restaurant\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Restaurant\Resources\Expenses\Pages\EditExpense;
use App\Filament\Restaurant\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Restaurant\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\Restaurant\Resources\Expenses\Tables\ExpensesTable;
use App\Models\Expense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'التقارير والمالية';

    protected static ?string $navigationLabel = 'إدارة المصاريف';

    protected static ?string $modelLabel = 'مصروف';

    protected static ?string $pluralModelLabel = 'المصاريف والرواتب';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('restaurant_id', Auth::user()?->restaurant_id ?? 0);
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->role === 'restaurant_admin' && filled($user->restaurant_id);
    }

    public static function canView(Model $record): bool
    {
        return static::canManageRecord($record);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManageRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManageRecord($record);
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }

    private static function canManageRecord(Model $record): bool
    {
        $user = Auth::user();

        return static::canViewAny()
            && (int) $record->getAttribute('restaurant_id') === (int) $user->restaurant_id;
    }
}
