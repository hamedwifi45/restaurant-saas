<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverRatingController extends Controller
{
    public function store(Request $request, string $slug, Order $order): RedirectResponse
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
                fn (mixed $token): bool => is_string($token) && hash_equals($order->device_token, $token)
            );

        abort_unless($hasValidDeviceToken, 403, 'لا تملك صلاحية تقييم المندوب لهذا الطلب.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);

        $wasRated = DB::transaction(function () use ($order, $restaurant, $validated): bool {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->restaurant_id === $restaurant->id, 404);
            abort_unless($lockedOrder->status === 'delivered' && $lockedOrder->driver_id !== null, 422, 'يمكن تقييم المندوب بعد تسليم الطلب فقط.');

            if ($lockedOrder->driver_rating !== null) {
                return false;
            }

            User::query()->whereKey($lockedOrder->driver_id)->lockForUpdate()->firstOrFail();
            $lockedOrder->forceFill(['driver_rating' => $validated['rating']])->save();

            $averageRating = Order::query()
                ->where('driver_id', $lockedOrder->driver_id)
                ->whereNotNull('driver_rating')
                ->avg('driver_rating');

            Driver::query()
                ->where('user_id', $lockedOrder->driver_id)
                ->update(['rating' => $averageRating]);

            return true;
        });

        return redirect()->route('order.track', [
            'slug' => $slug,
            'code' => $order->tracking_code,
            'token' => $order->device_token,
        ])->with(
            $wasRated ? 'success' : 'info',
            $wasRated ? 'شكراً لتقييمك للمندوب.' : 'لقد قمت بتقييم المندوب لهذا الطلب مسبقاً.'
        );
    }
}
