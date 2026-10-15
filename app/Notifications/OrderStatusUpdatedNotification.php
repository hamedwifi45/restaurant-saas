<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $status,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['broadcast', 'database', WebPushChannel::class];
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
     * @return array<string, int|string|null>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $payload = $this->payload();
        $restaurant = $this->order->restaurant;

        return (new WebPushMessage)
            ->title($payload['title'])
            ->body($payload['message'])
            ->icon(media_url($restaurant->logo) ?? asset('apple-touch-icon.png'))
            ->dir('rtl')
            ->lang('ar')
            ->tag('order-'.$this->order->tracking_code)
            ->data([
                'url' => route('order.track', [
                    'slug' => $restaurant->slug,
                    'code' => $this->order->tracking_code,
                ]),
            ]);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function payload(): array
    {
        [$title, $message, $notificationStatus] = match ($this->status) {
            'preparing' => ['بدأ تحضير الطلب', 'بدأ المطبخ في تحضير طلبك رقم '.$this->order->tracking_code.'.', 'preparing'],
            'ready' => ['الطلب جاهز', 'طلبك رقم '.$this->order->tracking_code.' جاهز الآن.', 'ready'],
            'on_way', 'out_for_delivery' => ['الطلب في الطريق', 'الطلب رقم '.$this->order->tracking_code.' في الطريق إليك مع المندوب.', 'out_for_delivery'],
            'driver_arrived' => ['وصول المندوب', 'وصل المندوب إلى موقعك للطلب رقم '.$this->order->tracking_code.'.', 'driver_arrived'],
            'delivered' => ['تم تسليم الطلب', 'تم تسليم الطلب رقم '.$this->order->tracking_code.' بنجاح. شكراً لتعاملك معنا.', 'delivered'],
            'cancelled' => [
                'إلغاء الطلب',
                'تم إلغاء الطلب رقم '.$this->order->tracking_code.'.',
                'cancelled',
            ],
            default => throw new InvalidArgumentException('Unsupported order notification status: '.$this->status),
        };

        return [
            'title' => $title,
            'message' => $message,
            'order_id' => $this->order->id,
            'tracking_code' => $this->order->tracking_code,
            'status' => $notificationStatus,
            'restaurant_id' => $this->order->restaurant_id,
            'rejection_reason' => $notificationStatus === 'cancelled' ? $this->order->rejection_reason : null,
        ];
    }
}
