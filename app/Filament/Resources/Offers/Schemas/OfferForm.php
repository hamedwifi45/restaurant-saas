<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // القسم الأول: المعلومات الأساسية
                Section::make('المعلومات الأساسية')
                    ->schema([
                        TextInput::make('title')
                            ->label('عنوان العرض')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: خصم 20% على جميع المنتجات')
                            ->columnSpan(2),
                        
                        
                        Select::make('restaurant_id')
                            ->label('id المطعم')
                            ->required()
                            ->options(function () {
                                return \App\Models\Restaurant::pluck('name', 'id');
                            })
                            ->placeholder('اختر المطعم')
                            ->columnSpan(2)
                            ->visible(function ($get) {
                                return auth()->user()->role === 'super_admin';
                            }),
                            
                        
                        
                        Textarea::make('description')
                            ->label('وصف العرض')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('اكتب وصفاً جذاباً للعرض...')
                            ->columnSpanFull(),
                        
                        FileUpload::make('image')
                            ->label('صورة العرض')
                            ->image()
                            ->directory('offers')
                            ->maxSize(2048)
                            ->columnSpan(2),
                        
                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('هل هذا العرض ظاهر للزبائن؟'),
                    ])->columns(2),

                // القسم الثاني: نوع العرض والقيمة
                Section::make('نوع العرض والقيمة')
                    ->schema([
                        Select::make('type')
                            ->label('نوع العرض')
                            ->options([
                                'percentage' => 'نسبة مئوية (%)',
                                'fixed_amount' => 'مبلغ ثابت (ر.س)',
                                'free_product' => 'منتج مجاني',
                                'free_shipping' => 'شحن مجاني',
                            ])
                            ->required()
                            ->reactive()
                            ->default('percentage')
                            ->columnSpan(2),
                        
                        TextInput::make('value')
                            ->label('قيمة الخصم')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->placeholder('0')
                            ->helperText(function ($get) {
                                return match($get('type')) {
                                    'percentage' => 'النسبة المئوية (مثال: 20)',
                                    'fixed_amount' => 'المبلغ بالريال (مثال: 10)',
                                    default => 'اتركه 0',
                                };
                            })
                            ->visible(function ($get) {
                                return in_array($get('type'), ['percentage', 'fixed_amount']);
                            })
                            ->columnSpan(2),
                        
                        TextInput::make('min_order_amount')
                            ->label('الحد الأدنى للطلب (ل.س)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->placeholder('0')
                            ->helperText('الحد الأدنى لتطبيق العرض (0 = بدون حد)')
                            ->columnSpan(2),
                    ])->columns(2),

                // القسم الثالث: فترة العرض
                Section::make('فترة العرض')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('تاريخ البداية')
                            ->native(false)
                            ->minDate(now())
                            ->helperText('اتركه فارغاً للبدء فوراً')
                            ->columnSpan(2),
                        
                        DateTimePicker::make('ends_at')
                            ->label('تاريخ النهاية')
                            ->native(false)
                            ->minDate(now())
                            ->afterOrEqual('starts_at')
                            ->helperText('اتركه فارغاً لعرض غير محدود')
                            ->columnSpan(2),
                        
                        TextInput::make('max_uses')
                            ->label('الحد الأقصى للاستخدامات')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('غير محدود')
                            ->helperText('كم مرة يمكن استخدام هذا العرض؟')
                            ->columnSpan(2),
                    ])->columns(2),

                // القسم الرابع: المنتجات المشمولة
                Section::make('المنتجات المشمولة')
                    ->schema([
                        Toggle::make('apply_to_all')
                            ->label('تطبيق على جميع المنتجات')
                            ->default(true)
                            ->reactive()
                            ->helperText('إذا تم تعطيله، يمكنك اختيار منتجات محددة')
                            ->columnSpanFull(),
                        
                        Select::make('product_ids')
                            ->label('اختر المنتجات')
                            ->options(function () {
                                $restaurantId = auth()->user()->restaurant_id;
                                return Product::where('restaurant_id', $restaurantId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn($get) => !$get('apply_to_all'))
                            ->helperText('اختر المنتجات التي ينطبق عليها العرض')
                            ->columnSpanFull(),
                    ]),

                // حقول مخفية
                TextInput::make('restaurant_id')
                    ->default(fn() => auth()->user()->restaurant_id)
                    ->hidden(),
                
                TextInput::make('used_count')
                    ->default(0)
                    ->hidden(),
            ]);
    }
}
