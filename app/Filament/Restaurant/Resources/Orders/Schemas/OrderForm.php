<?php

namespace App\Filament\Restaurant\Resources\Orders\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->label('المستخدم')
                    ->numeric(),
                TextInput::make('customer_name')
                    ->label('اسم العميل')
                    ->required(),
                TextInput::make('customer_phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->required(),
                Select::make('driver_id')
                    ->label('مندوب التوصيل')
                    ->options(fn (): array => User::query()
                        ->where('role', 'driver')
                        ->where('restaurant_id', auth()->user()->restaurant_id)
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                TextInput::make('customer_email')
                    ->label('البريد الإلكتروني')
                    ->email(),
                TextInput::make('delivery_type')
                    ->label('نوع التوصيل')
                    ->required()
                    ->default('delivery'),
                TextInput::make('restaurant_table_id')
                    ->label('رقم الطاولة')
                    ->numeric()
                    ->disabled(),
                DateTimePicker::make('dine_in_arrival_at')
                    ->label('موعد الوصول للمطعم'),
                TextInput::make('dine_in_guest_count')
                    ->label('عدد الضيوف')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(20),
                Select::make('dine_in_seating_preference')
                    ->label('تفضيل الجلسة')
                    ->options([
                        'any' => 'بدون تفضيل',
                        'indoor' => 'داخلية',
                        'outdoor' => 'خارجية',
                        'window' => 'بجانب النافذة',
                        'quiet' => 'مكان هادئ',
                        'family' => 'جلسة عائلية',
                    ]),
                Select::make('dine_in_occasion')
                    ->label('المناسبة')
                    ->options([
                        'none' => 'زيارة عادية',
                        'birthday' => 'عيد ميلاد',
                        'anniversary' => 'ذكرى سنوية',
                        'family' => 'لقاء عائلي',
                        'business' => 'اجتماع عمل',
                        'other' => 'أخرى',
                    ]),
                TextInput::make('dine_in_high_chair_count')
                    ->label('عدد كراسي الأطفال')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10),
                Toggle::make('dine_in_stroller_space')
                    ->label('مساحة لعربة أطفال'),
                Textarea::make('dine_in_accessibility_notes')
                    ->label('متطلبات الوصول أو الحركة')
                    ->columnSpanFull(),
                Textarea::make('dine_in_allergy_notes')
                    ->label('الحساسية والمكونات الواجب تجنبها')
                    ->columnSpanFull(),
                Select::make('dine_in_dietary_preference')
                    ->label('التفضيل الغذائي')
                    ->options([
                        'none' => 'لا يوجد',
                        'vegetarian' => 'نباتي',
                        'vegan' => 'نباتي صرف',
                        'gluten_free' => 'خالٍ من الغلوتين',
                        'other' => 'أخرى',
                    ]),
                Toggle::make('dine_in_confirmation_call')
                    ->label('الاتصال لتأكيد الحجز'),
                Textarea::make('dine_in_celebration_notes')
                    ->label('تجهيزات المناسبة')
                    ->columnSpanFull(),
                Textarea::make('delivery_address')
                    ->label('عنوان التوصيل')
                    ->columnSpanFull(),
                TextInput::make('delivery_city')
                    ->label('المدينة'),
                TextInput::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('subtotal')
                    ->label('الإجمالي الفرعي')
                    ->required()
                    ->numeric(),
                TextInput::make('discount')
                    ->label('الخصم')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_amount')
                    ->label('الإجمالي')
                    ->required()
                    ->numeric(),
                TextInput::make('requested_pickup_at')
                    ->label('موعد الاستلام')
                    ->disabled(),
                TextInput::make('deposit_amount')
                    ->label('العربون')
                    ->numeric()
                    ->disabled(),
                Select::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'pending' => 'بانتظار مراجعة الإيصال',
                        'deposit_verified' => 'تم تأكيد العربون',
                        'verified' => 'مدفوع بالكامل',
                        'rejected' => 'إيصال مرفوض',
                    ])
                    ->required(),
                FileUpload::make('payment_receipt')
                    ->label('إيصال الدفع المسبق')
                    ->disk('local')
                    ->directory('payment-receipts')
                    ->visibility('private')
                    ->openable()
                    ->downloadable()
                    ->disabled(),
                TextInput::make('status')
                    ->label('الحالة')
                    ->required()
                    ->default('pending'),
                Textarea::make('notes')
                    ->label('الملاحظات')
                    ->columnSpanFull(),
                Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->columnSpanFull(),
                TextInput::make('tracking_code')
                    ->label('رمز التتبع'),
                TextInput::make('rating')
                    ->label('التقييم')
                    ->numeric(),
                Textarea::make('review')
                    ->label('التعليق')
                    ->columnSpanFull(),
            ]);
    }
}
