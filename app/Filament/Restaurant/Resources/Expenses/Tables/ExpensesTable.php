<?php

namespace App\Filament\Restaurant\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        $currency = Auth::user()?->restaurant?->currency_symbol ?: 'ل.س';

        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان المصروف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->sortable()
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2).' '.$currency),
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
            ->filters([
                Filter::make('report_period')
                    ->label('فترة التقرير')
                    ->schema([
                        DatePicker::make('spent_from')->label('من تاريخ'),
                        DatePicker::make('spent_until')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['spent_from'] ?? null)) {
                            $query->whereDate('spent_at', '>=', $data['spent_from']);
                        }

                        if (filled($data['spent_until'] ?? null)) {
                            $query->whereDate('spent_at', '<=', $data['spent_until']);
                        }

                        return $query;
                    }),
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
