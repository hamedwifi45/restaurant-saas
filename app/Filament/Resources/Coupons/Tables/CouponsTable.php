<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'percentage',
                        'warning' => 'fixed_amount',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'percentage' => 'نسبة مئوية',
                        'fixed_amount' => 'مبلغ ثابت',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('القيمة')
                    ->formatStateUsing(fn($record) => $record->getDiscountLabel())
                    ->sortable(),
                
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('نشط'),
                
                Tables\Columns\TextColumn::make('used_count')
                    ->label('الاستخدامات')
                    ->sortable()
                    ->formatStateUsing(fn($record) => 
                        $record->used_count . ' / ' . ($record->max_uses ?? '∞')
                    ),
                
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('يبدأ')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('ينتهي')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'percentage' => 'نسبة مئوية',
                        'fixed_amount' => 'مبلغ ثابت',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
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