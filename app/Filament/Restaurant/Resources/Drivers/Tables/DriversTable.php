<?php

namespace App\Filament\Restaurant\Resources\Drivers\Tables;

use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DriversTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('driverProfile.avatar')
                    ->label('الصورة')
                    ->circular(),
                TextColumn::make('name')
                    ->label('السائق')
                    ->description(fn (User $record): ?string => $record->phone)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('driverProfile.vehicle_type')
                    ->label('نوع المركبة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'motorcycle', 'دراجة نارية', 'دراجة نارية (موتور)' => 'دراجة نارية',
                        'car', 'سيارة', 'سيارة توصيل' => 'سيارة توصيل',
                        'bicycle', 'دراجة', 'دراجة هوائية' => 'دراجة هوائية',
                        default => $state ?? 'غير محدد',
                    })
                    ->icon(fn (?string $state): ?string => match ($state) {
                        'motorcycle', 'دراجة نارية', 'دراجة نارية (موتور)' => 'heroicon-o-bolt',
                        'car', 'سيارة', 'سيارة توصيل' => 'heroicon-o-truck',
                        'bicycle', 'دراجة', 'دراجة هوائية' => 'heroicon-o-sparkles',
                        default => null,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'motorcycle', 'دراجة نارية', 'دراجة نارية (موتور)' => 'warning',
                        'car', 'سيارة', 'سيارة توصيل' => 'info',
                        'bicycle', 'دراجة', 'دراجة هوائية' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('driverProfile.plate_number')
                    ->label('رقم اللوحة / الترخيص')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                ToggleColumn::make('driverProfile.is_available')
                    ->label('حالة التفرغ')
                    ->getStateUsing(fn (User $record): bool => (bool) data_get($record->driverProfile, 'is_available', true))
                    ->updateStateUsing(fn (User $record, bool $state): bool => (bool) $record->driverProfile()->updateOrCreate([], [
                        'is_available' => $state,
                    ])->is_available),
                TextColumn::make('completed_orders_count')
                    ->label('الطلبات المكتملة')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('driverProfile.rating')
                    ->label('التقييم')
                    ->formatStateUsing(fn ($state): string => $state === null
                        ? '—'
                        : '★ '.number_format((float) $state, 1))
                    ->sortable(),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('تواصل عبر الواتساب')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (User $record): string => 'https://wa.me/'.preg_replace('/\D+/', '', $record->phone ?? ''))
                    ->openUrlInNewTab()
                    ->visible(fn (User $record): bool => filled($record->phone)),
                Action::make('active_orders')
                    ->label('عرض الطلبات الحالية')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->modalHeading(fn (User $record): string => 'الطلبات الحالية - '.$record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (User $record) => view('filament.restaurant.drivers.active-orders', [
                        'orders' => Order::query()
                            ->where('driver_id', $record->getKey())
                            ->where('restaurant_id', $record->restaurant_id)
                            ->whereNotIn('status', ['delivered', 'cancelled'])
                            ->latest()
                            ->get(),
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
