<?php

namespace App\Filament\Restaurant\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('عنوان المصروف')
                    ->required()
                    ->maxLength(255),
                TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->suffix('ل.س'),
                Select::make('category')
                    ->label('تصنيف المصروف')
                    ->options([
                        'operational' => 'تشغيلي وعام',
                        'salaries' => 'رواتب ومكافآت',
                        'raw_materials' => 'مواد أولية ومشتريات',
                        'utilities' => 'فواتير وخدمات',
                    ])
                    ->required()
                    ->default('operational'),
                DatePicker::make('spent_at')
                    ->label('تاريخ الصرف')
                    ->required()
                    ->default(now()),
                Textarea::make('notes')
                    ->label('ملاحظات إضافية')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
