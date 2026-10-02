<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerBroadcastAuthController extends Controller
{
    /**
     * Issue the Pusher-compatible authorization signature after validating the order device token.
     */
    public function authorizeCustomerChannel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'socket_id' => ['required', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string', 'max:255'],
        ]);

        if (! preg_match('/^private-restaurant\.(\d+)\.customer\.(.+)$/', $validated['channel_name'], $matches)) {
            abort(403);
        }

        $order = Order::query()
            ->where('restaurant_id', $matches[1])
            ->where('tracking_code', $matches[2])
            ->firstOrFail();

        $cookieToken = $request->cookie('order_device_token_'.$order->id);
        $sessionToken = $request->session()->get('order_device_tokens.'.$order->tracking_code);
        $hasValidDeviceToken = filled($order->device_token)
            && ((is_string($cookieToken) && hash_equals($order->device_token, $cookieToken))
                || (is_string($sessionToken) && hash_equals($order->device_token, $sessionToken)));

        abort_unless($hasValidDeviceToken, 403);

        $key = config('broadcasting.connections.reverb.key');
        $secret = config('broadcasting.connections.reverb.secret');

        if (! is_string($key) || $key === '' || ! is_string($secret) || $secret === '') {
            throw new HttpException(503, 'تعذر تفعيل الاتصال الفوري حالياً.');
        }

        $signature = hash_hmac(
            'sha256',
            $validated['socket_id'].':'.$validated['channel_name'],
            $secret,
        );

        return response()->json([
            'auth' => $key.':'.$signature,
        ]);
    }
}
