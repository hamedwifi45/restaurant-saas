<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NotificationChannels\WebPush\PushSubscription;

class OrderPushSubscriptionController extends Controller
{
    public function store(Request $request, string $slug, Order $order): JsonResponse
    {
        $restaurant = Restaurant::query()->where('slug', $slug)->firstOrFail();
        abort_unless($order->restaurant_id === $restaurant->id, 404);

        $deviceTokens = [
            $request->input('token'),
            $request->cookie('order_device_token_'.$order->id),
            $request->session()->get('order_device_tokens.'.$order->tracking_code),
        ];
        $hasValidDeviceToken = filled($order->device_token)
            && collect($deviceTokens)->contains(
                fn ($token): bool => is_string($token) && hash_equals($order->device_token, $token)
            );

        abort_unless($hasValidDeviceToken, 403, 'لا تملك صلاحية تفعيل إشعارات هذا الطلب.');

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'starts_with:https://', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        $order->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
        );

        return response()->json(['success' => true]);
    }
}
