<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RestaurantOrderAvailabilityController extends Controller
{
    public function update(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && (int) $user->restaurant_id === (int) $restaurant->id
                && in_array($user->role, ['restaurant_admin', 'kitchen_staff'], true),
            403
        );

        $validated = $request->validate([
            'is_accepting_orders' => ['required', 'boolean'],
        ]);

        $restaurant->update([
            'is_accepting_orders' => $validated['is_accepting_orders'],
        ]);

        $message = $restaurant->is_accepting_orders
            ? 'تم تفعيل استقبال الطلبات.'
            : 'تم إيقاف استقبال الطلبات مؤقتاً.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_accepting_orders' => $restaurant->is_accepting_orders,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
