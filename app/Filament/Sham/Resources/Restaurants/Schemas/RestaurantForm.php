<?php

namespace App\Filament\Sham\Resources\Restaurants\Schemas;

use App\Models\PlatformFeature;
use App\Models\Restaurant;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as DbSchema;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المطعم')
                    ->schema([
                        TextInput::make('name')->label('اسم المطعم')->required()->maxLength(255),
                        TextInput::make('slug')->label('الرابط المختصر')->required()->unique(ignoreRecord: true)->maxLength(255),
                        TextInput::make('subdomain')
                            ->label('النطاق الفرعي')
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->maxLength(63)
                            ->rules(['regex:/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/']),
                        Select::make('plan')
                            ->label('الباقة (Plan)')
                            ->options(fn (): array => self::planOptions())
                            ->required()
                            ->default('starter')
                            ->helperText('يرث المطعم الميزات المشمولة في الباقة المختارة من كتالوج الباقات، مع إمكانية تخصيص أو تعطيل أي ميزة أدناه.'),
                        Textarea::make('description')->label('الوصف')->columnSpanFull(),
                        Select::make('theme_id')->label('التصميم')->relationship('theme', 'name')->required()->searchable(),
                        FileUpload::make('logo')->label('الشعار')->image()->disk('public')->directory('restaurants/logos'),
                        TextInput::make('logo_url')->label('أو رابط الشعار')->url()->maxLength(2048),
                        FileUpload::make('cover_image')->label('صورة الغلاف')->image()->disk('public')->directory('restaurants/covers'),
                        TextInput::make('cover_image_url')->label('أو رابط صورة الغلاف')->url()->maxLength(2048),
                    ])
                    ->columns(2),
                Section::make('حساب مالك المطعم')
                    ->schema([
                        TextInput::make('owner_name')->label('اسم المالك')->required()->maxLength(255),
                        TextInput::make('owner_email')
                            ->label('بريد المالك')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignorable: fn (?Restaurant $record) => $record?->restaurantAdmin,
                            ),
                        TextInput::make('owner_password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('owner_phone')->label('هاتف المالك')->tel()->maxLength(30),
                    ])
                    ->columns(2),
                Section::make('التواصل والتشغيل')
                    ->schema([
                        TextInput::make('phone')->label('رقم الهاتف')->tel()->maxLength(30),
                        TextInput::make('email')->label('البريد الإلكتروني')->email()->maxLength(255),
                        Textarea::make('address')->label('العنوان الكامل')->columnSpanFull(),
                        TextInput::make('city')->label('المدينة')->maxLength(120),
                        TextInput::make('delivery_fee')->label('رسوم التوصيل')->numeric()->minValue(0)->default(0),
                        TextInput::make('estimated_delivery_time')->label('وقت التوصيل المتوقع (دقيقة)')->numeric()->suffix('دقيقة'),
                        TextInput::make('currency_symbol')->label('رمز العملة')->maxLength(12)->default('ل.س'),
                        TextInput::make('tax_percentage')->label('نسبة الضريبة')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(0),
                    ])
                    ->columns(2),
                Section::make('التصميم والدفع')
                    ->schema([
                        ColorPicker::make('primary_color')->label('اللون الأساسي')->default('#FF6B35'),
                        ColorPicker::make('secondary_color')->label('اللون الثانوي')->default('#FFFFFF'),
                        ColorPicker::make('background_color')->label('لون الخلفية')->default('#1A1A1A'),
                        FileUpload::make('qr_code_image')->label('صورة QR الدفع')->image()->disk('public')->directory('restaurants/qrcodes'),
                        TextInput::make('qr_code_image_url')->label('أو رابط صورة QR الدفع')->url()->maxLength(2048),
                        Textarea::make('bank_details')->label('تفاصيل الحساب البنكي')->columnSpanFull(),
                        Select::make('pricing_type')->label('نموذج التسعير')->options(['commission' => 'عمولة على الطلبات', 'subscription' => 'اشتراك شهري ثابت'])->default('commission'),
                        TextInput::make('commission_rate')->label('نسبة العمولة (%)')->numeric()->minValue(0)->maxValue(100)->step(0.01)->suffix('%')->default(0),
                        TextInput::make('subscription_fee')->label('رسوم الاشتراك الشهري')->numeric()->minValue(0)->step(0.01)->default(0),
                        TextInput::make('whatsapp_number')->label('رقم واتساب')->tel()->maxLength(30),
                        TextInput::make('instagram_url')->label('رابط إنستغرام')->url()->maxLength(2048),
                        TextInput::make('facebook_url')->label('رابط فيسبوك')->url()->maxLength(2048),
                        Toggle::make('is_active')->label('الحساب نشط')->default(true),
                        DatePicker::make('trial_ends_at')->label('نهاية الفترة التجريبية')->native(false),
                    ])
                    ->columns(2),
                Section::make('تخصيص الميزات وشركات التوصيل للمطعم')
                    ->description('إتاحة أو إزالة ميزات مخصصة لهذا المطعم فوق باقته الأساسية، وربط شركات التوصيل المؤهلة.')
                    ->schema([
                        Select::make('platformFeatures')
                            ->label('الميزات المفعّلة خصيصاً للمطعم')
                            ->relationship('platformFeatures', 'name_ar')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('تُعرض الميزات مع أسعارها التقريبية من كتالوج الميزات وتُطبّق صلاحياتها مباشرة في الخادم.'),
                        Select::make('deliveryProviders')
                            ->label('شركات التوصيل المرتبطة بالمطعم')
                            ->relationship('deliveryProviders', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function planOptions(): array
    {
        $options = [
            'starter' => 'Starter (الأساسية)',
            'pro' => 'Pro (الاحترافية)',
        ];

        try {
            if (DbSchema::hasTable('subscription_plans')) {
                foreach (SubscriptionPlan::query()->where('is_active', true)->orderBy('sort_order')->get() as $plan) {
                    $options[$plan->slug] = "{$plan->name_ar} ({$plan->monthly_price}$ / شهرياً)";
                }
            }
        } catch (\Throwable) {
        }

        return $options;
    }
}
