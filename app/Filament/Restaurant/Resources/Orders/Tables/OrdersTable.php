<?php

namespace App\Filament\Restaurant\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
                TextColumn::make('restaurantTable.number')
                    ->label('الطاولة')
                    ->searchable(),
                TextColumn::make('dine_in_arrival_at')
                    ->label('موعد الوصول')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('requested_pickup_at')
                    ->label('موعد الاستلام')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('dine_in_guest_count')
                    ->label('الضيوف')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('delivery_city')
                    ->label('المدينة')
                    ->searchable(),
                TextColumn::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('distance_km')
                    ->label('المسافة')
                    ->suffix(' كم')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('customer_lat')
                    ->label('موقع العميل')
                    ->state(fn (Order $record): ?string => filled($record->customer_lat ?? $record->delivery_latitude)
                        && filled($record->customer_lng ?? $record->delivery_longitude)
                        ? 'عرض الموقع'
                        : null)
                    ->url(fn (Order $record): ?string => filled($record->customer_lat ?? $record->delivery_latitude)
                        && filled($record->customer_lng ?? $record->delivery_longitude)
                        ? 'https://www.google.com/maps?q='.($record->customer_lat ?? $record->delivery_latitude).','.($record->customer_lng ?? $record->delivery_longitude)
                        : null)
                    ->openUrlInNewTab()
                    ->placeholder('—'),
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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit_verified' => 'تم تأكيد العربون',
                        'verified' => 'مدفوع بالكامل',
                        'rejected' => 'إيصال مرفوض',
                        default => 'بانتظار مراجعة الإيصال',
                    })
                    ->searchable(),
                TextColumn::make('payment_receipt')
                    ->label('إيصال الدفع')
                    ->state(fn (Order $record): ?string => filled($record->payment_receipt) ? 'مرفق' : null)
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('deposit_amount')
                    ->label('العربون')
                    ->numeric()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->searchable(),
                TextColumn::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->placeholder('—')
                    ->toggleable(),
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
                Action::make('reject')
                    ->label('رفض الطلب')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Order $record): bool => $record->status !== 'cancelled'
                        && (auth()->user()?->can('update', $record) ?? false))
                    ->schema([
                        Select::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->options([
                                'خارج نطاق التوصيل' => 'خارج نطاق التوصيل',
                                'العنوان غير واضح' => 'العنوان غير واضح',
                                'نفاد وجبات المطبخ' => 'نفاد وجبات المطبخ',
                                'أخرى' => 'أخرى',
                            ])
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        abort_unless(auth()->user()?->can('update', $record), 403);

                        $record->update([
                            'status' => 'cancelled',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
