<?php

namespace App\Http\Controllers;

use App\Events\OrderReadyForDelivery;
use App\Helpers\ThemeHelper;
use App\Http\Resources\KdsOrderResource;
use App\Models\KdsOrderReceipt;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\LocalRestaurantPairing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KdsController extends Controller
{
    public function __construct(private readonly LocalRestaurantPairing $pairing) {}

    /**
     * جلب كافة الطلبات النشطة لشاشة المطبخ (Pending, Preparing, Ready)
     */
    public function index(string $slug): View
    {
        $restaurant = $this->restaurantForSlug($slug);

        return $this->renderBoard($restaurant);
    }

    public function orders(string $slug): AnonymousResourceCollection
    {
        $restaurant = $this->restaurantForSlug($slug);

        return KdsOrderResource::collection($this->activeOrders($restaurant));
    }

    public function acknowledgeOrders(Request $request, string $slug): JsonResponse
    {
        $restaurant = $this->restaurantForSlug($slug);
        $validated = $request->validate([
            'client_order_uuids' => ['required', 'array', 'min:1', 'max:100'],
            'client_order_uuids.*' => ['required', 'uuid', 'distinct'],
        ]);

        $orders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('checkout_token', $validated['client_order_uuids'])
            ->get(['id', 'restaurant_id', 'checkout_token']);

        abort_unless($orders->count() === count($validated['client_order_uuids']), 404);

        $now = now();
        KdsOrderReceipt::query()->insertOrIgnore(
            $orders->map(static fn (Order $order): array => [
                'restaurant_id' => $restaurant->id,
                'order_id' => $order->id,
                'client_order_uuid' => $order->checkout_token,
                'acknowledged_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
        );

        return response()->json([
            'acknowledged' => true,
            'client_order_uuids' => $orders->pluck('checkout_token')->values(),
        ]);
    }

    private function renderBoard(Restaurant $restaurant): View
    {
        $orderCollection = $this->activeOrders($restaurant);

        $kdsOrders = $orderCollection->map(static fn (Order $order): array => [
            'id' => $order->tracking_code,
            'tracking_code' => $order->tracking_code,
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'customer_lat' => $order->customer_lat ?? $order->delivery_latitude,
            'customer_lng' => $order->customer_lng ?? $order->delivery_longitude,
            'distance_km' => $order->distance_km,
            'table' => $order->restaurantTable?->number,
            'dine_in_arrival_at' => $order->dine_in_arrival_at?->toIso8601String(),
            'dine_in_duration_minutes' => $order->dine_in_duration_minutes,
            'requested_pickup_at' => $order->requested_pickup_at?->toIso8601String(),
            'deposit_amount' => (float) $order->deposit_amount,
            'payment_status' => $order->payment_status,
            'dine_in_guest_count' => $order->dine_in_guest_count,
            'dine_in_allergy_notes' => $order->dine_in_allergy_notes,
            'dine_in_confirmation_call' => $order->dine_in_confirmation_call,
            'items' => $order->items->map(static fn ($item): array => [
                'product_name' => $item->product_name,
                'name' => $item->name,
                'variant_name' => $item->variant_name,
                'notes' => $item->notes ?? '',
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->price,
                'total' => (float) $item->total,
            ])->values(),
        ])->values();

        $view = ThemeHelper::viewOrFallback(
            $restaurant,
            'pages.kds-board',
            'themes.albasha-burger.pages.kds-board',
        );

        return view($view, compact('restaurant', 'orderCollection', 'kdsOrders'));
    }

    /**
     * @return Collection<int, Order>
     */
    private function activeOrders(Restaurant $restaurant): Collection
    {
        return Order::with(['items', 'restaurantTable', 'driver', 'kdsReceipt'])
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->where(function (Builder $query): void {
                $query->where('deposit_amount', '<=', 0)
                    ->orWhereIn('payment_status', ['deposit_verified', 'verified']);
            })
            ->where(function (Builder $query): void {
                $query->whereNull('dine_in_arrival_at')
                    ->orWhere('reservation_status', 'confirmed');
            })
            ->get();
    }

    public function updateStatusForRestaurant(Request $request, string $slug, string $orderId): JsonResponse
    {
        $this->restaurantForSlug($slug);

        return $this->updateStatus($request, $orderId);
    }

    /**
     * تحديث حالة الطلب عند تفاعل الطاهي مع الشاشة
     */
    public function updateStatus(Request $request, string $orderId): JsonResponse
    {
        // 1. التحقق من صحة الحالة المجهزة
        $validated = $request->validate([
            'status' => 'required|in:new,preparing,ready,completed',
        ]);

        // 2. البحث عن الطلب وتحديث حالته
        $this->authorizeKdsAccess();
        $order = $this->authorizedOrder($orderId);

        $nextStatus = match ($validated['status']) {
            'new' => 'pending',
            'completed' => 'delivered',
            default => $validated['status'],
        };

        $allowedNextStatuses = [
            'pending' => 'preparing',
            'preparing' => 'ready',
            'ready' => 'delivered',
        ];

        abort_unless(
            $order->status === $nextStatus || ($allowedNextStatuses[$order->status] ?? null) === $nextStatus,
            409,
            'لا يمكن نقل الطلب إلى هذه الحالة من حالته الحالية.',
        );

        $order->status = $nextStatus;
        $order->save();
        if ($order->status === 'ready') {
            broadcast(new OrderReadyForDelivery($order))->toOthers();
        }

        // 3. إرجاع استجابة JSON للـ JavaScript
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'status' => $validated['status'],
        ]);
    }

    public function rejectOrder(Request $request, string $orderId): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                Rule::in([
                    'خارج نطاق التوصيل',
                    'العنوان غير واضح',
                    'نفاد وجبات المطبخ',
                    'أخرى',
                ]),
            ],
        ]);

        $this->authorizeKdsAccess();
        $order = $this->authorizedOrder($orderId);
        abort_if($order->status === 'cancelled', 409, 'تم رفض الطلب مسبقاً.');

        $order->update([
            'status' => 'cancelled',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'status' => $order->status,
            'rejection_reason' => $order->rejection_reason,
        ]);
    }

    public function rejectOrderForRestaurant(Request $request, string $slug, string $orderId): JsonResponse
    {
        $this->restaurantForSlug($slug);

        return $this->rejectOrder($request, $orderId);
    }

    private function authorizeRestaurant(Restaurant $restaurant): void
    {
        $this->authorizeKdsAccess();

        abort_unless((int) auth()->user()->restaurant_id === (int) $restaurant->id, 403);

        $hostRestaurant = request()->attributes->get('restaurant');
        abort_unless(
            $hostRestaurant === null
                || ($hostRestaurant instanceof Restaurant && $hostRestaurant->is($restaurant)),
            403,
        );
    }

    private function restaurantForSlug(string $slug): Restaurant
    {
        $restaurant = $this->pairing->isEnabled()
            ? $this->pairing->restaurantForSlug($slug)
            : Restaurant::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();
        $this->authorizeRestaurant($restaurant);

        return $restaurant;
    }

    private function authorizeKdsAccess(): void
    {
        Gate::authorize('access-kds');
    }

    private function authorizedOrder(string $orderId): Order
    {
        $query = Order::query()
            ->where('tracking_code', $orderId);
        $hostRestaurant = request()->attributes->get('restaurant');

        if ($hostRestaurant instanceof Restaurant) {
            $query->where('restaurant_id', $hostRestaurant->id);
        }

        $order = $query->firstOrFail();
        abort_unless((int) $order->restaurant_id === (int) auth()->user()->restaurant_id, 403);
        $this->pairing->assertMatches($order->restaurant);

        return $order;
    }
}
