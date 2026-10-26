<?php

namespace App\Filament\Sham\Resources\PlatformFeatures;

use App\Filament\Sham\Resources\PlatformFeatures\Pages\CreatePlatformFeature;
use App\Filament\Sham\Resources\PlatformFeatures\Pages\EditPlatformFeature;
use App\Filament\Sham\Resources\PlatformFeatures\Pages\ListPlatformFeatures;
use App\Filament\Sham\Resources\PlatformFeatures\Schemas\PlatformFeatureForm;
use App\Filament\Sham\Resources\PlatformFeatures\Tables\PlatformFeaturesTable;
use App\Models\PlatformFeature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PlatformFeatureResource extends Resource
{
    protected static ?string $model = PlatformFeature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المنصة';

    protected static ?string $navigationLabel = 'كتالوج الميزات والتسعير';

    protected static ?string $modelLabel = 'ميزة منصة';

    protected static ?string $pluralModelLabel = 'ميزات المنصة والتسعير التقريبي';

    protected static ?string $recordTitleAttribute = 'name_ar';

    public static function form(Schema $schema): Schema
    {
        return PlatformFeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformFeaturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatformFeatures::route('/'),
            'create' => CreatePlatformFeature::route('/create'),
            'edit' => EditPlatformFeature::route('/{record}/edit'),
        ];
    }
}
