<?php

namespace App\Filament\Sham\Resources\DeliveryProviders;

use App\Filament\Sham\Resources\DeliveryProviders\Pages\CreateDeliveryProvider;
use App\Filament\Sham\Resources\DeliveryProviders\Pages\EditDeliveryProvider;
use App\Filament\Sham\Resources\DeliveryProviders\Pages\ListDeliveryProviders;
use App\Filament\Sham\Resources\DeliveryProviders\Schemas\DeliveryProviderForm;
use App\Filament\Sham\Resources\DeliveryProviders\Tables\DeliveryProvidersTable;
use App\Models\DeliveryProvider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryProviderResource extends Resource
{
    protected static ?string $model = DeliveryProvider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المنصة';

    protected static ?string $navigationLabel = 'شركات التوصيل';

    protected static ?string $modelLabel = 'شركة توصيل';

    protected static ?string $pluralModelLabel = 'شركات التوصيل';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DeliveryProviderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryProvidersTable::configure($table);
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
            'index' => ListDeliveryProviders::route('/'),
            'create' => CreateDeliveryProvider::route('/create'),
            'edit' => EditDeliveryProvider::route('/{record}/edit'),
        ];
    }
}
