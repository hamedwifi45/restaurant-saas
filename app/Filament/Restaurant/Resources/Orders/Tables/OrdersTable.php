<?php

namespace App\Filament\Restaurant\Resources\Orders\Tables;

use App\Models\DeliveryDispatch;
use App\Models\DeliveryDispatchEvent;
use App\Models\DeliveryProvider;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\DeliveryDispatchService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deliveryDispatch.status')
                    ->label('إحالة شركة التوصيل')
                    ->placeholder('—')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => self::dispatchStatusLabel($state)),
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
                Filter::make('report_period')
                    ->label('تصفية التقرير')
                    ->schema([
                        DatePicker::make('created_from')->label('من تاريخ'),
                        DatePicker::make('created_until')->label('إلى تاريخ'),
                        Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'confirmed' => 'مؤكد',
                                'preparing' => 'قيد التحضير',
                                'ready' => 'جاهز',
                                'on_the_way' => 'في الطريق',
                                'delivered' => 'مكتمل',
                                'cancelled' => 'ملغي',
                            ]),
                        Select::make('payment_status')
                            ->label('حالة الدفع')
                            ->options([
                                'pending' => 'بانتظار الدفع',
                                'deposit_verified' => 'تم تأكيد العربون',
                                'verified' => 'مدفوع بالكامل',
                                'rejected' => 'إيصال مرفوض',
                            ]),
                        Select::make('invoice_status')
                            ->label('حالة الفاتورة')
                            ->options([
                                'pending' => 'بانتظار الدفع',
                                'paid' => 'مدفوعة',
                                'cancelled' => 'ملغاة',
                                'refunded' => 'مستردة',
                            ]),
                        Select::make('product_id')
                            ->label('المنتج')
                            ->searchable()
                            ->options(fn (): array => Product::query()
                                ->where('restaurant_id', auth('web')->user()?->restaurant_id ?? 0)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),
                        Checkbox::make('verified_sales')
                            ->label('الطلبات المدفوعة وغير الملغاة فقط'),
                        Checkbox::make('non_cancelled_only')
                            ->label('استبعاد الطلبات الملغاة'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['created_from'] ?? null) || filled($data['created_until'] ?? null)) {
                            $query->whereNull('deleted_at');
                        }

                        if (filled($data['created_from'] ?? null)) {
                            $query->whereDate('created_at', '>=', $data['created_from']);
                        }

                        if (filled($data['created_until'] ?? null)) {
                            $query->whereDate('created_at', '<=', $data['created_until']);
                        }

                        if (filled($data['status'] ?? null)) {
                            $query->where('status', $data['status']);
                        }

                        if (filled($data['payment_status'] ?? null)) {
                            $query->where('payment_status', $data['payment_status']);
                        }

                        if (filled($data['invoice_status'] ?? null)) {
                            $query->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery
                                ->where('status', $data['invoice_status']));
                        }

                        if (filled($data['product_id'] ?? null)) {
                            $query->whereHas('items', fn (Builder $itemQuery): Builder => $itemQuery
                                ->where('product_id', $data['product_id']));
                        }

                        if (filter_var($data['verified_sales'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                            $query->verifiedNonCancelled();
                        } elseif (filter_var($data['non_cancelled_only'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                            $query->where('status', '!=', 'cancelled');
                        }

                        return $query;
                    }),
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
                Action::make('queue_external_delivery')
                    ->label('إحالة لشركة توصيل')
                    ->icon('heroicon-o-truck')
                    ->visible(function (Order $record): bool {
                        $user = Auth::user();
                        $restaurant = $user instanceof User ? $user->restaurant : null;
                        $dispatch = $record->deliveryDispatch;

                        return $record->delivery_type === 'delivery'
                            && $record->status === 'ready'
                            && $record->driver_id === null
                            && (! $dispatch instanceof DeliveryDispatch
                                || $dispatch->status === DeliveryDispatch::STATUS_CANCELLED)
                            && $restaurant instanceof Restaurant
                            && (int) $record->restaurant_id === (int) $restaurant->getKey()
                            && $restaurant->deliveryFulfillmentAvailable()
                            && $restaurant->externalDeliveryEnabled()
                            && $user->can('update', $record);
                    })
                    ->schema(function (Order $record): array {
                        $providerOptions = self::eligibleProviderOptions($record);

                        if ($providerOptions === []) {
                            return [
                                Placeholder::make('no_eligible_provider')
                                    ->label('لا توجد شركة متاحة')
                                    ->content('اربط شركة توصيل نشطة من إعدادات التوصيل قبل تسجيل الإحالة.'),
                            ];
                        }

                        return [
                            Select::make('delivery_provider_id')
                                ->label('شركة التوصيل')
                                ->options($providerOptions)
                                ->required()
                                ->searchable(),
                            Placeholder::make('customer_delivery_fee')
                                ->label('رسوم التوصيل المسجلة على الطلب')
                                ->content(fn (Order $record): string => number_format((float) $record->delivery_fee, 2).' '.(Auth::user()?->restaurant?->currency_symbol ?? '')),
                            TextInput::make('provider_fee')
                                ->label('الأجرة التي أكّدتها الشركة')
                                ->helperText('أدخل المبلغ بعملة المطعم. يُسجل للمقارنة فقط ولا يُضاف إلى فاتورة العميل أو يعتبر تسوية مالية.')
                                ->numeric()
                                ->minValue(0)
                                ->step('0.01')
                                ->required(),
                            TextInput::make('provider_reference')
                                ->label('مرجع الشركة')
                                ->maxLength(255),
                            Textarea::make('notes')
                                ->label('ملاحظات الإحالة')
                                ->maxLength(2000),
                        ];
                    })
                    ->action(function (
                        Order $record,
                        array $data,
                        DeliveryDispatchService $dispatchService,
                    ): void {
                        $actor = Auth::user();
                        abort_unless($actor instanceof User, 403);
                        $restaurant = $actor->restaurant;
                        abort_unless(
                            $restaurant instanceof Restaurant
                                && (int) $record->restaurant_id === (int) $restaurant->getKey(),
                            403,
                        );

                        $providerId = filter_var($data['delivery_provider_id'] ?? null, FILTER_VALIDATE_INT);
                        if ($providerId === false || $providerId < 1) {
                            throw ValidationException::withMessages([
                                'delivery_provider_id' => 'لا توجد شركة نشطة ومؤهلة مرتبطة بهذا المطعم.',
                            ]);
                        }

                        $provider = DeliveryProvider::query()->findOrFail($providerId);
                        $dispatchService->queueManualDispatch(
                            $restaurant,
                            $record,
                            $provider,
                            $actor,
                            [
                                'provider_fee' => $data['provider_fee'] ?? null,
                                'provider_reference' => $data['provider_reference'] ?? null,
                                'notes' => $data['notes'] ?? null,
                            ],
                        );

                        Notification::make()
                            ->title('تم تسجيل الإحالة اليدوية')
                            ->body('لم يُرسل أي طلب تلقائياً إلى الشركة. تابع التواصل والحالة يدوياً.')
                            ->success()
                            ->send();
                    }),
                Action::make('update_external_delivery_status')
                    ->label('تحديث إحالة الشركة')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(function (Order $record): bool {
                        $user = Auth::user();
                        $restaurant = $user instanceof User ? $user->restaurant : null;
                        $dispatch = $record->deliveryDispatch;

                        return $dispatch instanceof DeliveryDispatch
                            && (
                                in_array($dispatch->status, DeliveryDispatch::activeStatuses(), true)
                                || $dispatch->status === DeliveryDispatch::STATUS_DELIVERED
                            )
                            && $restaurant instanceof Restaurant
                            && (int) $record->restaurant_id === (int) $restaurant->getKey()
                            && $user->can('update', $record);
                    })
                    ->schema(function (Order $record): array {
                        $dispatch = $record->deliveryDispatch;
                        $providerName = $dispatch?->provider?->name ?? 'شركة التوصيل';
                        $providerPhone = $dispatch?->provider?->phone;
                        $dispatchHistory = $dispatch?->events
                            ->sortBy('id')
                            ->map(fn (DeliveryDispatchEvent $event): string => implode(' — ', array_filter([
                                self::dispatchStatusLabel($event->to_status),
                                $event->actor?->name,
                                $event->created_at?->format('Y-m-d H:i'),
                                $event->note,
                            ])))
                            ->implode(' | ');

                        return [
                            Placeholder::make('current_dispatch_status')
                                ->label('الحالة الحالية')
                                ->content(self::dispatchStatusLabel($dispatch?->status)),
                            Placeholder::make('provider_contact')
                                ->label('بيانات التواصل')
                                ->content($providerPhone ? $providerName.' · '.$providerPhone : $providerName),
                            Placeholder::make('dispatch_history')
                                ->label('سجل الإحالة')
                                ->content($dispatchHistory ?: 'لا توجد أحداث مسجلة.'),
                            Select::make('status')
                                ->label('الحالة المؤكدة الجديدة')
                                ->options(self::nextDispatchStatuses($dispatch?->status))
                                ->required(),
                            Textarea::make('confirmation_note')
                                ->label('ملاحظة تأكيد التواصل')
                                ->helperText('سجل ما أكّدته الشركة عبر التواصل اليدوي.')
                                ->required()
                                ->minLength(3)
                                ->maxLength(2000),
                        ];
                    })
                    ->action(function (
                        Order $record,
                        array $data,
                        DeliveryDispatchService $dispatchService,
                    ): void {
                        $actor = Auth::user();
                        abort_unless($actor instanceof User, 403);
                        $restaurant = $actor->restaurant;
                        $dispatch = $record->deliveryDispatch;
                        abort_unless(
                            $restaurant instanceof Restaurant
                                && (int) $record->restaurant_id === (int) $restaurant->getKey()
                                && $dispatch instanceof DeliveryDispatch,
                            403,
                        );

                        $dispatchService->transition(
                            $restaurant,
                            $dispatch,
                            $actor,
                            (string) ($data['status'] ?? ''),
                            (string) ($data['confirmation_note'] ?? ''),
                        );

                        Notification::make()
                            ->title('تم تحديث حالة إحالة الشركة')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int|string, string>
     */
    private static function eligibleProviderOptions(Order $record): array
    {
        $user = Auth::user();
        $restaurant = $user instanceof User ? $user->restaurant : null;

        if (! $restaurant instanceof Restaurant || (int) $record->restaurant_id !== (int) $restaurant->getKey()) {
            return [];
        }

        return $restaurant->eligibleDeliveryProviders()
            ->select([
                'delivery_providers.id',
                'delivery_providers.name',
                'delivery_providers.contact_name',
                'delivery_providers.phone',
                'delivery_providers.email',
                'delivery_providers.pricing_notes',
            ])
            ->orderBy('delivery_providers.name')
            ->get()
            ->mapWithKeys(function (DeliveryProvider $provider): array {
                $details = collect([
                    $provider->contact_name,
                    $provider->phone,
                    $provider->email,
                    $provider->pricing_notes,
                ])->filter(fn (?string $value): bool => filled($value));

                return [
                    $provider->getKey() => $details->isEmpty()
                        ? $provider->name
                        : $provider->name.' — '.$details->implode(' · '),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function nextDispatchStatuses(?string $currentStatus): array
    {
        return match ($currentStatus) {
            DeliveryDispatch::STATUS_QUEUED => [
                DeliveryDispatch::STATUS_ACCEPTED => 'أكدت الشركة قبول الطلب',
                DeliveryDispatch::STATUS_CANCELLED => 'تم تأكيد إلغاء الإحالة',
            ],
            DeliveryDispatch::STATUS_ACCEPTED => [
                DeliveryDispatch::STATUS_PICKED_UP => 'أكدت الشركة استلام الطلب',
                DeliveryDispatch::STATUS_CANCELLED => 'تم تأكيد إلغاء الإحالة',
            ],
            DeliveryDispatch::STATUS_PICKED_UP => [
                DeliveryDispatch::STATUS_DELIVERED => 'تم تأكيد تسليم الطلب',
            ],
            default => [],
        };
    }

    private static function dispatchStatusLabel(?string $status): ?string
    {
        return match ($status) {
            DeliveryDispatch::STATUS_QUEUED => 'إحالة مسجلة؛ بانتظار التواصل اليدوي',
            DeliveryDispatch::STATUS_ACCEPTED => 'أكدت الشركة قبول الطلب',
            DeliveryDispatch::STATUS_PICKED_UP => 'أكدت الشركة استلام الطلب',
            DeliveryDispatch::STATUS_DELIVERED => 'تم تأكيد التسليم',
            DeliveryDispatch::STATUS_CANCELLED => 'أُلغيت الإحالة',
            default => null,
        };
    }
}
