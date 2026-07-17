<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // القسم الأول: المعلومات الأساسية
                Section::make('المعلومات الأساسية')
                    ->schema([
                        TextInput::make('code')
                            ->label('كود الخصم')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('مثال: SAVE20')
                            ->helperText('الكود الذي سيدخله الزبون (بالأحرف الكبيرة)')
                            ->columnSpan(2),
                        
                        TextInput::make('name')
                            ->label('اسم الكوبون (داخلي)')
                            ->maxLength(255)
                            ->placeholder('مثال: كوبون رمضان')
                            ->helperText('اسم وصفي لك فقط، لا يظهر للزبون')
                            ->columnSpan(2),
                        
                        Textarea::make('description')
                            ->label('وصف الكوبون')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('وصف اختياري للكوبون')
                            ->columnSpanFull(),
                        
                        Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('هل هذا الكوبون متاح للاستخدام؟'),
                    ])->columns(2),

                // القسم الثاني: نوع الخصم والقيمة
                Section::make('نوع الخصم والقيمة')
                    ->schema([
                        Select::make('type')
                            ->label('نوع الخصم')
                            ->options([
                                'percentage' => 'نسبة مئوية (%)',
                                'fixed_amount' => 'مبلغ ثابت (ر.س)',
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
                                };
                            })
                            ->columnSpan(2),
                        
                        TextInput::make('min_order_amount')
                            ->label('الحد الأدنى للطلب (ر.س)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->placeholder('0')
                            ->helperText('الحد الأدنى لتطبيق الكوبون (0 = بدون حد)')
                            ->columnSpan(2),
                    ])->columns(2),

                // القسم الثالث: حدود الاستخدام
                Section::make('حدود الاستخدام')
                    ->schema([
                        TextInput::make('max_uses')
                            ->label('الحد الأقصى للاستخدامات')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('غير محدود')
                            ->helperText('كم مرة يمكن استخدام هذا الكوبون إجمالاً؟')
                            ->columnSpan(2),
                        
                        TextInput::make('max_uses_per_user')
                            ->label('الحد الأقصى لكل زبون')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('غير محدود')
                            ->helperText('كم مرة يمكن للزبون الواحد استخدام هذا الكوبون؟')
                            ->columnSpan(2),
                    ])->columns(2),

                // القسم الرابع: فترة الصلاحية
                Section::make('فترة الصلاحية')
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
                            ->helperText('اتركه فارغاً لعدم انتهاء الصلاحية')
                            ->columnSpan(2),
                    ])->columns(2),

                // القسم الخامس: المنتجات المشمولة
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
                            ->helperText('اختر المنتجات التي ينطبق عليها الكوبون')
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