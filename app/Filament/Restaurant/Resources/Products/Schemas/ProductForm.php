<?php

namespace App\Filament\Restaurant\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
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

                // اختيار نوع السعر
                Select::make('price_type')
                    ->label('نوع التسعير')
                    ->options([
                        'fixed' => 'سعر ثابت (محدد)',
                        'open_price' => 'مبلغ حر / مفتوح (مثل: فلافل بقيمة معينة)',
                        'variant' => 'أوزان / أحجام محددة (مثل: ربع كيلو، نصف كيلو)',
                    ])
                    ->default('fixed')
                    ->required()
                    ->live()
                    ->columnSpanFull(),

                // 1. حقول السعر الثابت (تظهر فقط إذا كان price_type === 'fixed')
                Grid::make(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('السعر')
                            ->required(fn ($get) => $get('price_type') === 'fixed')
                            ->numeric()
                            ->prefix('ل.س'),
                        TextInput::make('old_price')
                            ->label('السعر القديم')
                            ->numeric()
                            ->prefix('ل.س'),
                    ])
                    ->visible(fn ($get) => $get('price_type') === 'fixed' || ! $get('price_type')),

                // 2. حقول المبلغ الحر (تظهر فقط إذا كان price_type === 'open_price')
                Grid::make(2)
                    ->schema([
                        TextInput::make('min_price')
                            ->label('الحد الأدنى للمبلغ')
                            ->required(fn ($get) => $get('price_type') === 'open_price')
                            ->numeric()
                            ->default(1000)
                            ->prefix('ل.س'),
                        TextInput::make('max_price')
                            ->label('الحد الأقصى للمبلغ')
                            ->numeric()
                            ->default(100000)
                            ->prefix('ل.س'),
                    ])
                    ->visible(fn ($get) => $get('price_type') === 'open_price'),

                // 3. جدول الخيارات/الأوزان (يظهر فقط إذا كان price_type === 'variant')
                Repeater::make('variants')
                    ->label('خيارات الأوزان والأحجام')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الوزن/الخيار (مثال: ربع كيلو، نصف كيلو)')
                            ->required(),
                        TextInput::make('price')
                            ->label('السعر لهذا الوزن')
                            ->numeric()
                            ->required()
                            ->prefix('ل.س'),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->addActionLabel('إضافة خيار وزن جديد')
                    ->visible(fn ($get) => $get('price_type') === 'variant'),

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
                    ->required()
                    ->default(true),

                Toggle::make('is_featured')
                    ->label('مميز')
                    ->required()
                    ->default(false),

                TextInput::make('sort_order')
                    ->label('الترتيب')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
