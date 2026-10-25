<?php

namespace App\Filament\Restaurant\Resources\Staff\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الموظف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('الصفة / الدور')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'kitchen_staff' => 'طباخ / كادر المطبخ (KDS)',
                        'driver' => 'مندوب توصيل',
                        'cashier' => 'كاشير (نقطة البيع)',
                        'restaurant_admin' => 'مدير فرعي',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'kitchen_staff' => 'warning',
                        'driver' => 'info',
                        'cashier' => 'success',
                        'restaurant_admin' => 'success',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('حساب نشط')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاريخ الانضمام')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('الصفة / الدور')
                    ->options([
                        'kitchen_staff' => 'طباخ / كادر المطبخ (KDS)',
                        'driver' => 'مندوب توصيل',
                        'cashier' => 'كاشير (نقطة البيع)',
                        'restaurant_admin' => 'مدير فرعي',
                    ]),
                SelectFilter::make('is_active')
                    ->label('حالة الحساب')
                    ->options([
                        '1' => 'نشط',
                        '0' => 'غير نشط',
                    ]),
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
