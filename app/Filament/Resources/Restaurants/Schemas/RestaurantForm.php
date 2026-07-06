<?php

namespace App\Filament\Resources\Restaurants\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
       return $schema
            ->components([
                // --- المعلومات الأساسية ---
                TextInput::make('name')
                    ->label('اسم المطعم')
                    ->required()
                    ->maxLength(255),

                TextInput::make('slug')
                    ->label('الرابط المختصر')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('subdomain')
                    ->label('النطاق الفرعي')
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('الوصف')
                    ->columnSpanFull(),

                FileUpload::make('logo')
                    ->label('الشعار')
                    ->image()
                    ->directory('restaurants/logos'),

                FileUpload::make('cover_image')
                    ->label('صورة الغلاف')
                    ->image()
                    ->directory('restaurants/covers'),

                // --- معلومات التواصل والموقع ---
                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->nullable(),

                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->nullable(),

                Textarea::make('address')
                    ->label('العنوان الكامل')
                    ->nullable()
                    ->columnSpanFull(),

                TextInput::make('city')
                    ->label('المدينة')
                    ->nullable(),

                TextInput::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->numeric()
                    ->prefix('ل.س')
                    ->default(0),

                TextInput::make('estimated_delivery_time')
                    ->label('وقت التوصيل المتوقع (دقيقة)')
                    ->numeric()
                    ->suffix('دقيقة')
                    ->nullable(),

                // --- التصميم والألوان ---
                Select::make('theme_id')
                    ->label('التصميم')
                    ->relationship('theme', 'name')
                    ->required()
                    ->searchable(),

                ColorPicker::make('primary_color')
                    ->label('اللون الأساسي')
                    ->default('#FF6B35'),

                ColorPicker::make('secondary_color')
                    ->label('اللون الثانوي')
                    ->default('#FFFFFF'),

                ColorPicker::make('background_color')
                    ->label('لون الخلفية')
                    ->default('#1A1A1A'),

                // --- الدفع والتسعير ---
                FileUpload::make('qr_code_image')
                    ->label('صورة QR الدفع')
                    ->image()
                    ->directory('restaurants/qrcodes'),

                Textarea::make('bank_details')
                    ->label('تفاصيل الحساب البنكي')
                    ->nullable()
                    ->columnSpanFull(),

                Select::make('pricing_type')
                    ->label('نموذج التسعير')
                    ->options([
                        'commission' => 'عمولة على الطلبات',
                        'subscription' => 'اشتراك شهري ثابت',
                    ])
                    ->default('commission'),

                TextInput::make('commission_rate')
                    ->label('نسبة العمولة (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%'),

                TextInput::make('subscription_fee')
                    ->label('رسوم الاشتراك الشهري')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->prefix('ر.س'),

                // --- الحالة والفترة التجريبية ---
                Toggle::make('is_active')
                    ->label('مطعم مفعل')
                    ->default(true),

                DatePicker::make('trial_ends_at')
                    ->label('نهاية الفترة التجريبية')
                    ->nullable()
                    ->native(false),
            ]);
    }
}
