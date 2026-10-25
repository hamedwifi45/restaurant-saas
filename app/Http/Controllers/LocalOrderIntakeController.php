<?php

namespace App\Http\Controllers;

use App\Models\LocalOrderSyncOutbox;
use App\Models\User;
use App\Services\LocalOrderIntakeService;
use App\Support\LocalRestaurantPairing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LocalOrderIntakeController extends Controller
{
    public function __construct(
        private readonly LocalOrderIntakeService $orders,
        private readonly LocalRestaurantPairing $pairing,
    ) {}

    public function store(Request $request, string $slug): JsonResponse
    {
        abort_unless($this->pairing->isEnabled(), 404);

        $restaurant = $this->pairing->restaurantForSlug($slug);
        Gate::authorize('access-pos', $slug);
        $cashier = $request->user();
        abort_unless($cashier instanceof User && (int) $cashier->restaurant_id === (int) $restaurant->id, 403);

        $validated = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.variant_index' => ['nullable', 'integer', 'min:0'],
            'items.*.custom_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'checkout' => ['required', 'array:order_type,table_id,customer_name,customer_phone,address,latitude,longitude,notes,cash_received'],
            'checkout.order_type' => ['required', 'in:dine_in,takeaway,delivery'],
            'checkout.table_id' => ['nullable', 'integer'],
            'checkout.customer_name' => ['required', 'string', 'max:255'],
            'checkout.customer_phone' => ['required', 'string', 'max:20'],
            'checkout.address' => ['nullable', 'string', 'max:1000'],
            'checkout.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'checkout.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'checkout.notes' => ['nullable', 'string', 'max:1000'],
            'checkout.cash_received' => ['required', 'boolean'],
        ]);

        if (! Str::isUuid($validated['idempotency_key'])) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'معرّف الطلب غير صالح.',
            ]);
        }

        $cart = [];
        foreach ($validated['items'] as $index => $item) {
            $cart['local-'.$index] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'variant_index' => $item['variant_index'] ?? null,
                'custom_price' => $item['custom_price'] ?? null,
                'notes' => $item['notes'] ?? '',
            ];
        }

        $result = $this->orders->accept(
            $restaurant,
            $cashier,
            $cart,
            $validated['checkout'],
            $validated['idempotency_key'],
        );
        $order = $result['order'];
        $outboxStatus = LocalOrderSyncOutbox::query()
            ->where('order_id', $order->id)
            ->value('status');

        return response()->json([
            'data' => [
                'client_order_uuid' => $order->checkout_token,
                'tracking_code' => $order->tracking_code,
                'status' => $order->status,
                'item_count' => $order->items()->count(),
                'total' => (string) $order->final_amount,
                'invoice_number' => $order->invoice?->invoice_number,
            ],
            'outbox_status' => $outboxStatus,
            'created' => $result['created'],
        ], $result['created'] ? 201 : 200);
    }
}
