<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use RuntimeException;

class DriverAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $driverName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database'];
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.'.$this->order->restaurant_id.'.customer.'.$this->order->tracking_code),
            new PrivateChannel('restaurant.'.$this->order->restaurant_id.'.admin'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    /**
     * @return array<string, int|string>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, int|string>
     */
    private function payload(): array
    {
        return [
            'title' => 'تم تعيين المندوب',
            'message' => 'تم تعيين المندوب '.$this->cleanDriverName().' لتوصيل طلبك رقم '.$this->order->tracking_code.'.',
            'order_id' => $this->order->id,
            'tracking_code' => $this->order->tracking_code,
            'status' => 'driver_assigned',
            'restaurant_id' => $this->order->restaurant_id,
        ];
    }

    private function cleanDriverName(): string
    {
        $driverName = preg_replace(
            '/[\p{Extended_Pictographic}\p{Regional_Indicator}\x{FE0E}\x{FE0F}\x{200D}\x{20E3}\x{1F3FB}-\x{1F3FF}\x{E0020}-\x{E007F}]/u',
            '',
            $this->driverName,
        );

        if (! is_string($driverName)) {
            throw new RuntimeException('Unable to sanitize the assigned driver name.');
        }

        $driverName = preg_replace('/\s+/u', ' ', trim($driverName));

        if (! is_string($driverName)) {
            throw new RuntimeException('Unable to normalize the assigned driver name.');
        }

        return $driverName !== '' ? $driverName : 'المندوب';
    }
}
