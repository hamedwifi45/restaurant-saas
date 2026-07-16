<?php

namespace App\Filament\Resources\Offers\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('الصورة')
                    ->circular()
                    ->default('https://via.placeholder.com/50'),

                TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(30)
                    ->sortable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->colors([
                        'primary' => 'percentage',
                        'success' => 'free_product',
                        'warning' => 'fixed_amount',
                        'info' => 'free_shipping',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'percentage'    => 'نسبة مئوية',
                        'fixed_amount'  => 'مبلغ ثابت',
                        'free_product'  => 'منتج مجاني',
                        'free_shipping' => 'شحن مجاني',
                        default         => $state,
                    }),

                TextColumn::make('value')
                    ->label('القيمة')
                    ->formatStateUsing(fn($record) => $record->getDiscountLabel())
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('نشط'),

                TextColumn::make('used_count')
                    ->label('الاستخدامات')
                    ->sortable()
                    ->formatStateUsing(fn($record) => 
                        $record->used_count . ' / ' . ($record->max_uses ?? '∞')
                    ),

                TextColumn::make('starts_at')
                    ->label('يبدأ')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ends_at')
                    ->label('ينتهي')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'percentage'    => 'نسبة مئوية',
                        'fixed_amount'  => 'مبلغ ثابت',
                        'free_product'  => 'منتج مجاني',
                        'free_shipping' => 'شحن مجاني',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط'),
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