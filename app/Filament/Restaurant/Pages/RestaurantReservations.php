<?php

namespace App\Filament\Restaurant\Pages;

use App\Models\Order;
use App\Models\Restaurant;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\ReservationAvailabilityService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class RestaurantReservations extends Page
{
    protected string $view = 'filament.pages.restaurant-reservations';

    protected static ?string $title = 'حجوزات الطاولات';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'حجوزات الطاولات';

    protected static string|UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 3;

    public string $reservationDate = '';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'reservations';
    }

    public static function canAccess(): bool
    {
        $restaurant = Auth::user()?->restaurant;

        return Auth::user()?->role === 'restaurant_admin'
            && $restaurant?->theme?->folder_name === 'albasha-burger';
    }

    public function mount(): void
    {
        $this->reservationDate = '';
    }

    public function updatedReservationDate(): void
    {
        validator(
            ['reservationDate' => $this->reservationDate],
            ['reservationDate' => ['nullable', 'date_format:Y-m-d']],
        )->validate();
    }

    public function clearDateFilter(): void
    {
        $this->reservationDate = '';
    }

    /**
     * @return Collection<int, Order>
     */
    public function reservations(): Collection
    {
        return $this->restaurant()->orders()
            ->with(['restaurantTable', 'items'])
            ->where('delivery_type', 'dine_in')
            ->whereNotNull('dine_in_arrival_at')
            ->when(
                filled($this->reservationDate),
                fn (Builder $query): Builder => $query->whereDate('dine_in_arrival_at', $this->reservationDate),
            )
            ->orderBy('restaurant_table_id')
            ->orderBy('dine_in_arrival_at')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tableBoard(): array
    {
        $restaurant = $this->restaurant();
        $defaultDuration = (int) $restaurant->getExperimentalOrderFeatures()['reservation_duration_minutes'];
        $reservationSchedules = $this->reservations()->groupBy('restaurant_table_id');
        $orders = $restaurant->orders()
            ->where('delivery_type', 'dine_in')
            ->where('status', '!=', 'cancelled')
            ->where(function (Builder $query): void {
                $query->whereNull('reservation_status')
                    ->orWhere('reservation_status', '!=', 'rejected');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'rejected');
            })
            ->where(function (Builder $query): void {
                $query->where(function (Builder $walkInQuery): void {
                    $walkInQuery->whereNull('dine_in_arrival_at')
                        ->whereIn('status', ['pending', 'preparing', 'ready']);
                })->orWhere(function (Builder $reservationQuery): void {
                    $reservationQuery->whereNotNull('dine_in_arrival_at')
                        ->where('dine_in_arrival_at', '>=', now()->subHours(4));
                });
            })
            ->get([
                'id',
                'restaurant_table_id',
                'customer_name',
                'customer_phone',
                'status',
                'dine_in_arrival_at',
                'dine_in_duration_minutes',
                'reservation_status',
            ])
            ->groupBy('restaurant_table_id');

        return $restaurant->tables()
            ->orderBy('number')
            ->get()
            ->map(function ($table) use ($orders, $defaultDuration, $reservationSchedules): array {
                $tableOrders = $orders->get($table->id, collect());
                $walkInOrder = $tableOrders->first(
                    fn (Order $order): bool => $order->dine_in_arrival_at === null
                        && in_array($order->status, ['pending', 'preparing', 'ready'], true),
                );
                $now = now();
                $currentReservation = $tableOrders->first(function (Order $order) use ($now, $defaultDuration): bool {
                    if (! $order->dine_in_arrival_at) {
                        return false;
                    }

                    $end = $order->dine_in_arrival_at->copy()->addMinutes(
                        $order->dine_in_duration_minutes ?: $defaultDuration,
                    );

                    return $order->dine_in_arrival_at <= $now
                        && $end > $now
                        && in_array($order->reservation_status, ['pending', 'confirmed'], true);
                });
                $nextReservation = $tableOrders
                    ->filter(fn (Order $order): bool => $order->dine_in_arrival_at
                        && $order->dine_in_arrival_at > $now
                        && in_array($order->reservation_status, ['pending', 'confirmed'], true))
                    ->sortBy('dine_in_arrival_at')
                    ->first();

                $activeOrder = $walkInOrder ?? ($currentReservation?->reservation_status === 'confirmed' ? $currentReservation : null);

                return [
                    'id' => $table->id,
                    'number' => $table->number,
                    'name' => $table->name,
                    'seats' => $table->seats,
                    'active' => $table->is_active,
                    'state' => ! $table->is_active
                        ? 'inactive'
                        : ($activeOrder ? 'occupied' : ($currentReservation ? 'pending' : 'available')),
                    'customer_name' => $activeOrder?->customer_name ?? $currentReservation?->customer_name,
                    'customer_phone' => $activeOrder?->customer_phone ?? $currentReservation?->customer_phone,
                    'reservation' => $currentReservation,
                    'reservation_duration' => $currentReservation?->dine_in_duration_minutes ?: $defaultDuration,
                    'next_reservation' => $nextReservation,
                    'next_reservation_duration' => $nextReservation?->dine_in_duration_minutes ?: $defaultDuration,
                    'schedule' => $reservationSchedules->get($table->id, collect()),
                ];
            })
            ->all();
    }

    public function approveReservation(int $orderId, ReservationAvailabilityService $availability): void
    {
        abort_unless(static::canAccess(), 403);

        $restaurant = $this->restaurant();
        $order = DB::transaction(function () use ($availability, $orderId, $restaurant): Order {
            $lockedRestaurant = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();
            $order = $lockedRestaurant->orders()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($order->delivery_type === 'dine_in' && $order->dine_in_arrival_at, 404);
            abort_if($order->reservation_status !== 'pending', 409, 'تمت معالجة هذا الحجز مسبقاً.');

            $availableTable = $availability->availableTables(
                $lockedRestaurant,
                $order->dine_in_arrival_at,
                (int) $order->dine_in_guest_count,
                (int) ($order->dine_in_duration_minutes ?: $lockedRestaurant->getExperimentalOrderFeatures()['reservation_duration_minutes']),
                $order->id,
            )->firstWhere('id', $order->restaurant_table_id);

            if (! $availableTable) {
                Notification::make()
                    ->title('تعذر اعتماد الحجز')
                    ->body('أصبحت الطاولة مشغولة في الموعد. اختر طاولة أخرى أو تواصل مع العميل.')
                    ->danger()
                    ->send();

                return $order;
            }

            $order->update([
                'reservation_status' => 'confirmed',
                'payment_status' => 'deposit_verified',
            ]);

            return $order;
        });

        if ($order->reservation_status !== 'confirmed') {
            return;
        }

        $order->notify((new OrderStatusUpdatedNotification($order, 'reservation_confirmed'))->afterCommit());

        Notification::make()
            ->title('تم تأكيد الحجز وإرسال التأكيد للعميل')
            ->success()
            ->send();
    }

    public function rejectReservation(int $orderId): void
    {
        abort_unless(static::canAccess(), 403);

        $restaurant = $this->restaurant();
        $order = DB::transaction(function () use ($orderId, $restaurant): Order {
            $lockedRestaurant = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();
            $order = $lockedRestaurant->orders()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($order->delivery_type === 'dine_in' && $order->dine_in_arrival_at, 404);
            abort_if($order->reservation_status !== 'pending', 409, 'تمت معالجة هذا الحجز مسبقاً.');

            $order->update([
                'reservation_status' => 'rejected',
                'payment_status' => 'rejected',
                'status' => 'cancelled',
                'rejection_reason' => 'تعذر تأكيد حجز الطاولة.',
            ]);

            return $order;
        });

        Notification::make()
            ->title('تم رفض طلب الحجز وإبلاغ العميل')
            ->success()
            ->send();
    }

    private function restaurant(): Restaurant
    {
        return Auth::user()->restaurant;
    }
}
