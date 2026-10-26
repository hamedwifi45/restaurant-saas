<?php

namespace App\Filament\Sham\Resources\SubscriptionPlans\Tables;

use App\Models\SubscriptionPlan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label('المعرف (Slug)')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('name_ar')
                    ->label('اسم الباقة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monthly_price')
                    ->label('السعر الشهري')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('annual_price')
                    ->label('السعر السنوي')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('features_count')
                    ->label('عدد الميزات')
                    ->state(fn (SubscriptionPlan $record): int => count($record->feature_keys ?? []))
                    ->badge()
                    ->color('info'),
                TextColumn::make('calculated_valuation')
                    ->label('القيمة التقريبية للميزات')
                    ->state(fn (SubscriptionPlan $record): string => number_format($record->calculateApproximateFeaturesValue(), 2).' $'),
                IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->sortable(),
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
