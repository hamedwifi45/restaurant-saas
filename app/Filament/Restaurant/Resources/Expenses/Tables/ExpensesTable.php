<?php

namespace App\Filament\Restaurant\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان المصروف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2).' ل.س'),
                TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'salaries' => 'رواتب ومكافآت',
                        'raw_materials' => 'مواد أولية ومشتريات',
                        'utilities' => 'فواتير وخدمات',
                        default => 'تشغيلي وعام',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'salaries' => 'info',
                        'raw_materials' => 'success',
                        'utilities' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('spent_at')
                    ->label('تاريخ الصرف')
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('spent_at', 'desc');
    }
}
