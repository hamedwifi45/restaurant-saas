<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
                TextInput::make('customer_email')
                    ->label('البريد الإلكتروني')
                    ->email(),
                TextInput::make('delivery_type')
                    ->label('نوع التوصيل')
                    ->required()
                    ->default('delivery'),
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
                TextInput::make('payment_status')
                    ->label('حالة الدفع')
                    ->required()
                    ->default('pending'),
                TextInput::make('payment_receipt')
                    ->label('إيصال الدفع'),
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
