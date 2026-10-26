<?php

namespace App\Filament\Sham\Resources\PlatformFeatures\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PlatformFeaturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الميزة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->label('المفتاح البرمجي')
                    ->badge()
                    ->searchable(),
                TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge(),
                TextColumn::make('approximate_monthly_price')
                    ->label('السعر الشهري التقريبي')
                    ->sortable(),
                ToggleColumn::make('is_core')
                    ->label('أساسية'),
                ToggleColumn::make('is_active')
                    ->label('نشطة بالمنصة'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
