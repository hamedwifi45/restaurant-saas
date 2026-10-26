<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class LanPairingSignalController extends Controller
{
    /**
     * Store WebRTC SDP offer/answer signal in cache for POS <-> KDS LAN peer connection handshake.
     */
    public function publishSignal(Request $request, string $slug): JsonResponse
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();
        abort_unless(
            $user !== null
                && (int) $user->restaurant_id === (int) $restaurant->id
                && in_array($user->role, ['restaurant_admin', 'cashier', 'kitchen_staff'], true),
            403,
        );

        $validated = $request->validate([
            'role' => ['required', 'in:pos,kds'],
            'type' => ['required', 'in:offer,answer'],
            'sdp_payload' => ['required', 'string', 'max:32768'],
            'peer_id' => ['required', 'string', 'max:64'],
        ]);

        if ($validated['role'] === 'pos') {
            Gate::authorize('access-pos', $slug);
        } else {
            Gate::authorize('access-kds');
        }

        $cacheKey = $this->signalCacheKey($restaurant->id, $validated['type']);
        $signalData = [
            'role' => $validated['role'],
            'type' => $validated['type'],
            'sdp_payload' => $validated['sdp_payload'],
            'peer_id' => $validated['peer_id'],
            'published_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $signalData, now()->addMinutes(15));

        return response()->json([
            'stored' => true,
            'type' => $validated['type'],
            'published_at' => $signalData['published_at'],
        ]);
    }

    /**
     * Retrieve the latest WebRTC SDP offer/answer for this restaurant's LAN bridge.
     */
    public function pollSignal(Request $request, string $slug): JsonResponse
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();
        abort_unless(
            $user !== null
                && (int) $user->restaurant_id === (int) $restaurant->id
                && in_array($user->role, ['restaurant_admin', 'cashier', 'kitchen_staff'], true),
            403,
        );

        return response()->json([
            'offer' => Cache::get($this->signalCacheKey($restaurant->id, 'offer')),
            'answer' => Cache::get($this->signalCacheKey($restaurant->id, 'answer')),
        ]);
    }

    private function signalCacheKey(int $restaurantId, string $type): string
    {
        return "lan_webrtc_signal:{$restaurantId}:{$type}";
    }
}
