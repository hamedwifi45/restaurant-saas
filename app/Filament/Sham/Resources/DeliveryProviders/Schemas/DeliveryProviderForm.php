<?php

namespace App\Filament\Sham\Resources\DeliveryProviders\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات شركة التوصيل')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الشركة')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_name')
                            ->label('اسم جهة التواصل')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->maxLength(32),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Select::make('assignment_method')
                            ->label('طريقة الإحالة')
                            ->options([
                                'manual' => 'إحالة يدوية من المطعم',
                                'customer_selectable' => 'متاح لاختيار العميل عند غياب سائق المطعم',
                            ])
                            ->default('manual')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('الشركة نشطة')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('قواعد التسعير والمسافة والوقت التقديري')
                    ->description('تُستخدم لحساب رسوم التوصيل للعميل بدقة حسب مسار الطريق الفعلي أو النطاق الجغرافي.')
                    ->schema([
                        Select::make('pricing_model')
                            ->label('نموذج التسعير')
                            ->options([
                                'distance_per_km' => 'تسعير أساسي + لكل كيلومتر إضافي',
                                'zone_flat' => 'سعر ثابت حسب كل نطاق جغرافي',
                                'flat' => 'سعر ثابت موحد لجميع الطلبات',
                            ])
                            ->default('distance_per_km')
                            ->required(),
                        TextInput::make('base_fee')
                            ->label('الرسوم الأساسية')
                            ->numeric()
                            ->default(2.00)
                            ->required(),
                        TextInput::make('base_distance_km')
                            ->label('المسافة المشمولة بالرسوم الأساسية (كم)')
                            ->numeric()
                            ->default(2.00)
                            ->required(),
                        TextInput::make('per_km_fee')
                            ->label('رسوم كل كيلومتر إضافي')
                            ->numeric()
                            ->default(0.50)
                            ->required(),
                        TextInput::make('min_distance_km')
                            ->label('الحد الأدنى للمسافة (كم)')
                            ->numeric()
                            ->default(0.00),
                        TextInput::make('max_distance_km')
                            ->label('الحد الأقصى للمسافة المسموحة (كم)')
                            ->numeric()
                            ->default(25.00),
                        TextInput::make('min_order_amount')
                            ->label('الحد الأدنى لقيمة الطلب')
                            ->numeric()
                            ->default(0.00),
                        TextInput::make('estimated_pickup_minutes')
                            ->label('زمن وصول المندوب للاستلام (دقيقة)')
                            ->numeric()
                            ->default(15),
                        TextInput::make('road_detour_factor')
                            ->label('معامل انعطاف الطريق الاحتياطي (Road Factor)')
                            ->helperText('يُستخدم عند تعذر الاتصال بمحرك مسارات الطرق OSRM (الافتراضي 1.28).')
                            ->numeric()
                            ->default(1.28),
                        Textarea::make('pricing_notes')
                            ->label('ملاحظات وشروط إضافية')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('النطاقات الجغرافية المخدومة والمحظورة (Zones)')
                    ->description('حدد المناطق التي تخدمها الشركة (دوائر نصف قطر أو مضلعات) والمناطق المستثناة.')
                    ->schema([
                        Repeater::make('allowed_zones')
                            ->label('النطاقات المسموحة (Allowed Zones)')
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم المنطقة / الحي')
                                    ->required(),
                                Select::make('type')
                                    ->label('نوع النطاق')
                                    ->options([
                                        'circle' => 'نصف قطر دائري (Circle)',
                                        'polygon' => 'مضلع إحداثيات (Polygon)',
                                    ])
                                    ->default('circle')
                                    ->required(),
                                TextInput::make('center_lat')
                                    ->label('خط العرض للمركز (Lat)')
                                    ->numeric(),
                                TextInput::make('center_lng')
                                    ->label('خط الطول للمركز (Lng)')
                                    ->numeric(),
                                TextInput::make('radius_km')
                                    ->label('نصف القطر (كم)')
                                    ->numeric()
                                    ->default(5),
                                TextInput::make('flat_fee')
                                    ->label('سعر توصيل خاص بالمنطقة (اختياري)')
                                    ->numeric(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),

                        Repeater::make('blocked_zones')
                            ->label('المناطق المستثناة / المحظورة (Blocked Zones)')
                            ->schema([
                                TextInput::make('name')
                                    ->label('اسم المنطقة المحظورة')
                                    ->required(),
                                Select::make('type')
                                    ->label('نوع النطاق')
                                    ->options([
                                        'circle' => 'نصف قطر دائري (Circle)',
                                        'polygon' => 'مضلع إحداثيات (Polygon)',
                                    ])
                                    ->default('circle')
                                    ->required(),
                                TextInput::make('center_lat')
                                    ->label('خط العرض للمركز (Lat)')
                                    ->numeric(),
                                TextInput::make('center_lng')
                                    ->label('خط الطول للمركز (Lng)')
                                    ->numeric(),
                                TextInput::make('radius_km')
                                    ->label('نصف القطر (كم)')
                                    ->numeric()
                                    ->default(2),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
