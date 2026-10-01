<?php

namespace App\Filament\Restaurant\Resources\Invoices;

use App\Filament\Restaurant\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Restaurant\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Restaurant\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Restaurant\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Restaurant\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?string $navigationLabel = 'الفواتير';

    protected static ?string $modelLabel = 'فاتورة';

    protected static ?string $pluralModelLabel = 'الفواتير';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'الفوانير';

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
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
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        // إذا كان Super Admin، يرى كل الفواتير
        if ($user && $user->role === 'super_admin') {
            return $query;
        }

        // إذا كان مدير مطعم، يرى فواتير مطعمه فقط
        if ($user && filled($user->restaurant_id)) {
            $query->where('restaurant_id', $user->restaurant_id);
        } else {
            $query->whereNull('restaurant_id');
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['restaurant_admin', 'super_admin'], true);
    }
}
