<?php

namespace App\Filament\Sham\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('التصنيف')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('restaurant_id', auth()->user()->restaurant_id)
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('الاسم')
                    ->required(),
                TextInput::make('slug')
                    ->label('الرابط المختصر')
                    ->required(),
                Textarea::make('description')
                    ->label('الوصف')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('السعر')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('old_price')
                    ->label('السعر القديم')
                    ->numeric()
                    ->prefix('$'),
                FileUpload::make('image')
                    ->label('صورة المنتج')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->preventFilePathTampering()
                    ->disk('public')
                    ->directory('products')
                    ->maxSize(5120)
                    ->helperText('يرجى رفع صورة من جهازك بصيغة JPG أو PNG أو WEBP بحد أقصى 5 ميغابايت.'),
                Toggle::make('is_available')
                    ->label('متوفر')
                    ->required(),
                Toggle::make('is_featured')
                    ->label('مميز')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('الترتيب')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
