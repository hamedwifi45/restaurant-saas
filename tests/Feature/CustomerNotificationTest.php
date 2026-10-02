<?php

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Notifications\DriverAssignedNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Notification;

test('status changes queue and broadcast the correct Arabic customer notification', function () {
    $restaurant = Restaurant::factory()->create();
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'tracking_code' => 'ORD-ARABIC-1',
        'status' => 'pending',
    ]);
    Notification::fake();

    $order->update(['status' => 'preparing']);

    Notification::assertSentTo($order, OrderStatusUpdatedNotification::class, function (
        OrderStatusUpdatedNotification $notification,
        array $channels,
    ) use ($order): bool {
        $message = $notification->toBroadcast($order);
        $payload = $message->data;

        expect($notification)->toBeInstanceOf(ShouldQueue::class)
            ->and($channels)->toBe(['broadcast', 'database'])
            ->and($message)->toBeInstanceOf(BroadcastMessage::class)
            ->and($payload['title'])->toBe('بدأ تحضير الطلب')
            ->and($payload['message'])->toBe('بدأ المطبخ في تحضير طلبك رقم ORD-ARABIC-1.')
            ->and($payload['status'])->toBe('preparing')
            ->and(preg_match('/[\p{Emoji_Presentation}\p{Extended_Pictographic}]/u', $payload['title'].$payload['message']))->toBe(0)
            ->and($notification->broadcastOn())->toEqual([
                new PrivateChannel('restaurant.'.$order->restaurant_id.'.customer.'.$order->tracking_code),
                new PrivateChannel('restaurant.'.$order->restaurant_id.'.admin'),
            ]);

        return true;
    });
});

test('driver assignment persists and broadcasts the customer message', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
        'name' => 'أحمد 🚚 السائق',
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'tracking_code' => 'ORD-DRIVER-1',
    ]);
    Notification::fake();

    $order->update(['driver_id' => $driver->id]);

    Notification::assertSentTo($order, DriverAssignedNotification::class, function (
        DriverAssignedNotification $notification,
        array $channels,
    ) use ($order): bool {
        $payload = $notification->toBroadcast($order)->data;

        expect($channels)->toBe(['broadcast', 'database'])
            ->and($payload['message'])->toBe('تم تعيين المندوب أحمد السائق لتوصيل طلبك رقم ORD-DRIVER-1.')
            ->and(preg_match('/[\p{Emoji_Presentation}\p{Extended_Pictographic}]/u', $payload['message']))->toBe(0);

        return true;
    });
});

test('status notification content matches each delivery transition without emoji', function (
    string $status,
    string $expectedStatus,
    string $expectedMessage,
) {
    $restaurant = Restaurant::factory()->create();
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'tracking_code' => 'ORD-STATUS-1',
        'status' => 'pending',
    ]);
    Notification::fake();

    $order->update(['status' => $status]);

    Notification::assertSentTo($order, OrderStatusUpdatedNotification::class, function (
        OrderStatusUpdatedNotification $notification,
        array $channels,
    ) use ($order, $expectedStatus, $expectedMessage): bool {
        $payload = $notification->toBroadcast($order)->data;

        expect($channels)->toBe(['broadcast', 'database'])
            ->and($payload['status'])->toBe($expectedStatus)
            ->and($payload['message'])->toBe($expectedMessage)
            ->and(preg_match('/[\p{Emoji_Presentation}\p{Extended_Pictographic}]/u', $payload['title'].$payload['message']))->toBe(0);

        return true;
    });
})->with([
    'ready' => ['ready', 'ready', 'طلبك رقم ORD-STATUS-1 جاهز الآن.'],
    'out for delivery' => ['on_way', 'out_for_delivery', 'الطلب رقم ORD-STATUS-1 في الطريق إليك مع المندوب.'],
    'delivered' => ['delivered', 'delivered', 'تم تسليم الطلب رقم ORD-STATUS-1 بنجاح. شكراً لتعاملك معنا.'],
    'cancelled' => ['cancelled', 'cancelled', 'تم إلغاء الطلب رقم ORD-STATUS-1.'],
]);

test('customer notifications are stored in persistent order history', function () {
    $order = Order::factory()->create([
        'tracking_code' => 'ORD-HISTORY-1',
    ]);
    $notification = new OrderStatusUpdatedNotification($order, 'ready');

    Notification::sendNow($order, $notification, ['database']);

    $storedNotification = $order->notifications()->firstOrFail();

    expect($storedNotification->data['tracking_code'])->toBe('ORD-HISTORY-1')
        ->and($storedNotification->data['message'])->toBe('طلبك رقم ORD-HISTORY-1 جاهز الآن.');
});

test('customer tracking pages render the authenticated real-time notification toast', function () {
    $restaurant = Restaurant::factory()->create();
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'tracking_code' => 'ORD-TRACKING-1',
    ]);

    $this->get(route('order.track', [$restaurant->slug, $order->tracking_code]))
        ->assertSuccessful()
        ->assertSee('data-order-notifications', false)
        ->assertSee('data-tracking-code="'.$order->tracking_code.'"', false)
        ->assertSee(route('broadcasting.customer-auth'), false);
});

test('a customer device cannot authorize another order private channel', function () {
    $restaurant = Restaurant::factory()->create();
    $customerOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'device_token' => 'customer-device-token-one',
        'tracking_code' => 'ORD-CUSTOMER-1',
    ]);
    $foreignOrder = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'device_token' => 'customer-device-token-two',
        'tracking_code' => 'ORD-CUSTOMER-2',
    ]);

    config([
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
    ]);
    $socketId = '123.456';
    $customerChannel = 'private-restaurant.'.$restaurant->id.'.customer.'.$customerOrder->tracking_code;

    $this->withSession([
        'order_device_tokens' => [$customerOrder->tracking_code => $customerOrder->device_token],
    ])
        ->postJson(route('broadcasting.customer-auth'), [
            'socket_id' => $socketId,
            'channel_name' => 'private-restaurant.'.$restaurant->id.'.customer.'.$foreignOrder->tracking_code,
        ])
        ->assertForbidden();

    $this->withSession([
        'order_device_tokens' => [$customerOrder->tracking_code => $customerOrder->device_token],
    ])
        ->postJson(route('broadcasting.customer-auth'), [
            'socket_id' => $socketId,
            'channel_name' => $customerChannel,
        ])
        ->assertSuccessful()
        ->assertJsonPath(
            'auth',
            'test-key:'.hash_hmac('sha256', $socketId.':'.$customerChannel, 'test-secret'),
        );
});

test('a driver arrival is notified once and only by the assigned tenant driver', function () {
    $restaurant = Restaurant::factory()->create();
    $driver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $anotherDriver = User::factory()->create([
        'role' => 'driver',
        'restaurant_id' => $restaurant->id,
    ]);
    $order = Order::factory()->create([
        'restaurant_id' => $restaurant->id,
        'driver_id' => $driver->id,
        'status' => 'on_way',
    ]);
    Notification::fake();

    $this->actingAs($anotherDriver)
        ->postJson(route('driver.order.arrived', [$restaurant->slug, $order]))
        ->assertForbidden();

    $this->actingAs($driver)
        ->postJson(route('driver.order.arrived', [$restaurant->slug, $order]))
        ->assertSuccessful()
        ->assertJsonPath('already_notified', false);

    $this->actingAs($driver)
        ->postJson(route('driver.order.arrived', [$restaurant->slug, $order]))
        ->assertSuccessful()
        ->assertJsonPath('already_notified', true);

    expect($order->fresh()->driver_arrived_at)->not->toBeNull();
    Notification::assertSentToTimes($order, OrderStatusUpdatedNotification::class, 1);
    Notification::assertSentTo($order, OrderStatusUpdatedNotification::class, function (
        OrderStatusUpdatedNotification $notification,
    ) use ($order): bool {
        $payload = $notification->toBroadcast($order)->data;

        expect($payload['status'])->toBe('driver_arrived')
            ->and($payload['message'])->toBe('وصل المندوب إلى موقعك للطلب رقم '.$order->tracking_code.'.')
            ->and(preg_match('/[\p{Emoji_Presentation}\p{Extended_Pictographic}]/u', $payload['message']))->toBe(0);

        return true;
    });
});
