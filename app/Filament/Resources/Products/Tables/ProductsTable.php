<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('restaurant_id')
                    ->label('المطعم')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('category_id')
                    ->label('الفئة')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('الرابط')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('السعر')
                    ->money()
                    ->sortable(),
                TextColumn::make('old_price')
                    ->label('السعر القديم')
                    ->money()
                    ->sortable(),
                ImageColumn::make('image')
                    ->label('الصورة'),
                IconColumn::make('is_available')
                    ->label('متوفر')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('مميز')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('محذوف في')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
