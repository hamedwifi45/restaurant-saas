<?php

namespace App\Observers;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Events\ReservationUpdated;
use App\Models\DeliveryDispatch;
use App\Models\Order;
use App\Notifications\DriverAssignedNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderObserver
{
    public function updating(Order $order): void
    {
        if (! $order->isDirty('driver_id') || blank($order->driver_id)) {
            return;
        }

        if (
            ! $order->restaurant?->serviceEnabled('delivery')
            || ! $order->restaurant?->ownDeliveryDriversEnabled()
        ) {
            throw ValidationException::withMessages([
                'driver_id' => 'خدمة سائقي المطعم غير مفعّلة لهذا المطعم.',
            ]);
        }

        if ($order->deliveryDispatches()
            ->whereIn('status', DeliveryDispatch::activeStatuses())
            ->exists()) {
            throw ValidationException::withMessages([
                'driver_id' => 'لا يمكن تعيين سائق مطعم أثناء وجود إحالة توصيل نشطة.',
            ]);
        }
    }

    public function created(Order $order): void
    {
        if ($order->delivery_type === 'dine_in') {
            DB::afterCommit(static fn () => event(new ReservationUpdated($order)));
        }
    }

    public function updated(Order $order): void
    {
        if (
            ($order->wasChanged('payment_status') || $order->wasChanged('reservation_status'))
            && $order->deposit_amount > 0
            && in_array($order->payment_status, ['deposit_verified', 'verified'], true)
            && $order->status === 'pending'
            && (! $order->dine_in_arrival_at || $order->reservation_status === 'confirmed')
        ) {
            DB::afterCommit(static fn () => event(new OrderCreated($order)));
        }

        if (
            $order->delivery_type === 'dine_in'
            && (
                $order->wasChanged('reservation_status')
                || $order->wasChanged('restaurant_table_id')
                || $order->wasChanged('dine_in_arrival_at')
                || $order->wasChanged('dine_in_duration_minutes')
                || $order->wasChanged('status')
            )
        ) {
            DB::afterCommit(static fn () => event(new ReservationUpdated($order)));
        }

        if ($order->wasChanged('status')) {
            OrderStatusUpdated::dispatch($order);
        }

        if ($order->wasChanged('status') && in_array($order->status, [
            'preparing',
            'ready',
            'on_way',
            'out_for_delivery',
            'delivered',
            'cancelled',
        ], true)) {
            $order->notify((new OrderStatusUpdatedNotification($order, $order->status))->afterCommit());
        }

        if ($order->wasChanged('driver_id') && $order->driver_id !== null) {
            $driver = $order->driverUser()
                ->where('restaurant_id', $order->restaurant_id)
                ->where('role', 'driver')
                ->firstOrFail();

            $order->notify((new DriverAssignedNotification($order, $driver->name))->afterCommit());
        }
    }
}
