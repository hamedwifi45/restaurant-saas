<?php

namespace App\Filament\Sham\Resources\PlatformFeatures\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformFeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الميزة والتسعير التقريبي')
                    ->schema([
                        TextInput::make('key')
                            ->label('المفتاح البرمجي (Feature Key)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(64)
                            ->helperText('مثال: kds, qr_menu, gps_tracking, external_delivery_providers, ai_assistant'),
                        TextInput::make('name_ar')
                            ->label('اسم الميزة (بالعربية)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_en')
                            ->label('اسم الميزة (بالإنجليزية)')
                            ->maxLength(255),
                        Select::make('category')
                            ->label('التصنيف')
                            ->options([
                                'core' => 'أساسية (Core)',
                                'operations' => 'التشغيل والمطبخ (Operations)',
                                'delivery' => 'التوصيل والخرائط (Delivery)',
                                'payments' => 'المدفوعات (Payments)',
                                'analytics' => 'التحليلات والتقارير (Analytics)',
                                'branding' => 'الهوية والتطبيق (Branding & PWA)',
                                'ai' => 'الذكاء الاصطناعي (AI)',
                            ])
                            ->default('operations')
                            ->required(),
                        TextInput::make('approximate_monthly_price')
                            ->label('السعر الشهري التقريبي للميزة')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.50)
                            ->default(5.00)
                            ->required(),
                        TextInput::make('currency_code')
                            ->label('رمز العملة')
                            ->default('USD')
                            ->maxLength(12),
                        TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_core')
                            ->label('ميزة أساسية افتراضية')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('الميزة نشطة على مستوى المنصة')
                            ->helperText('إيقاف الميزة هنا يعطّلها في الخادم لدى جميع المطاعم فوراً.')
                            ->default(true),
                        Textarea::make('description_ar')
                            ->label('وصف الميزة')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
