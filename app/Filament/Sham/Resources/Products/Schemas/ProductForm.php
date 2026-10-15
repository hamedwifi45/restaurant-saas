<?php

namespace App\Filament\Sham\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

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
                Repeater::make('images')
                    ->label('معرض صور المنتج')
                    ->relationship()
                    ->defaultItems(0)
                    ->schema([
                        FileUpload::make('path')
                            ->label('الصورة')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->preventFilePathTampering(
                                allowFilePathUsing: fn (string $file): bool => str_starts_with($file, 'products/')
                                    && Storage::disk('public')->exists($file)
                            )
                            ->disk('public')
                            ->directory('products')
                            ->maxSize(5120)
                            ->required(),
                        TextInput::make('alt')
                            ->label('وصف الصورة')
                            ->maxLength(255),
                        Toggle::make('is_primary')
                            ->label('الصورة الرئيسية')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->orderColumn('sort_order')
                    ->columnSpanFull()
                    ->addActionLabel('إضافة صورة للمعرض')
                    ->helperText('يمكنك رفع عدة صور وترتيبها لتظهر في معرض المنتج.'),
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
