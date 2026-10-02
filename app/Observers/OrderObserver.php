<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\DriverAssignedNotification;
use App\Notifications\OrderStatusUpdatedNotification;

class OrderObserver
{
    public function updated(Order $order): void
    {
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
