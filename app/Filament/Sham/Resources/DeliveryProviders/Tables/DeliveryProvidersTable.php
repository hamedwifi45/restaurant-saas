<?php

namespace App\Filament\Sham\Resources\DeliveryProviders\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DeliveryProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الشركة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->placeholder('—'),
                TextColumn::make('assignment_method')
                    ->label('طريقة الإحالة')
                    ->formatStateUsing(fn (string $state): string => $state === 'manual' ? 'يدوية' : $state)
                    ->badge(),
                ToggleColumn::make('is_active')
                    ->label('نشطة'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
