<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('restaurant_id')
                    ->label('المطعم')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label('المستخدم')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('اسم العميل')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                TextColumn::make('customer_email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('delivery_type')
                    ->label('نوع التوصيل')
                    ->searchable(),
                TextColumn::make('delivery_city')
                    ->label('المدينة')
                    ->searchable(),
                TextColumn::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->label('الإجمالي الفرعي')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount')
                    ->label('الخصم')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->searchable(),
                TextColumn::make('payment_receipt')
                    ->label('إيصال الدفع')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->searchable(),
                TextColumn::make('tracking_code')
                    ->label('رمز التتبع')
                    ->searchable(),
                TextColumn::make('rating')
                    ->label('التقييم')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
