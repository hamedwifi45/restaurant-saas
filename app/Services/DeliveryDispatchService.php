<?php

namespace App\Services;

use App\Models\DeliveryDispatch;
use App\Models\DeliveryProvider;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DeliveryDispatchService
{
    /**
     * @param  array{provider_fee: int|float|string, notes?: string|null, provider_reference?: string|null}  $details
     */
    public function queueManualDispatch(
        Restaurant $restaurant,
        Order $order,
        DeliveryProvider $provider,
        User $actor,
        array $details,
    ): DeliveryDispatch {
        $validated = Validator::make($details, [
            'provider_fee' => ['required', 'numeric', 'decimal:0,2', 'between:0,99999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($restaurant, $order, $provider, $actor, $validated): DeliveryDispatch {
            $lockedRestaurant = Restaurant::query()
                ->whereKey($restaurant->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOrder = $lockedRestaurant->orders()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedProvider = DeliveryProvider::query()
                ->whereKey($provider->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('update', $lockedOrder);
            $this->ensureExternalDeliveryEnabled($lockedRestaurant);
            $this->ensureOrderCanBeDispatched($lockedOrder);
            $this->ensureProviderIsEligible($lockedRestaurant, $lockedProvider);

            if ($lockedOrder->deliveryDispatches()
                ->whereIn('status', DeliveryDispatch::activeStatuses())
                ->exists()) {
                throw ValidationException::withMessages([
                    'provider' => 'تم تسجيل طلب إحالة نشط لهذا الطلب مسبقاً.',
                ]);
            }

            $dispatch = $lockedOrder->deliveryDispatches()->create([
                'delivery_provider_id' => $lockedProvider->getKey(),
                'requested_by' => $actor->getKey(),
                'status' => DeliveryDispatch::STATUS_QUEUED,
                'customer_delivery_fee' => $lockedOrder->delivery_fee ?? 0,
                'provider_fee' => $validated['provider_fee'],
                'currency_symbol' => $lockedRestaurant->currency_symbol,
                'provider_reference' => $validated['provider_reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'queued_at' => now(),
            ]);

            $dispatch->events()->create([
                'actor_id' => $actor->getKey(),
                'from_status' => null,
                'to_status' => DeliveryDispatch::STATUS_QUEUED,
                'note' => $validated['notes'] ?? 'تم تسجيل إحالة يدوية؛ لم يتم إرسالها إلى الشركة تلقائياً.',
            ]);

            return $dispatch->load(['provider', 'events.actor']);
        }, 3);
    }

    public function transition(
        Restaurant $restaurant,
        DeliveryDispatch $dispatch,
        User $actor,
        string $nextStatus,
        string $confirmationNote,
    ): DeliveryDispatch {
        $validated = Validator::make([
            'status' => $nextStatus,
            'confirmation_note' => $confirmationNote,
        ], [
            'status' => ['required', 'in:'.implode(',', [
                DeliveryDispatch::STATUS_ACCEPTED,
                DeliveryDispatch::STATUS_PICKED_UP,
                DeliveryDispatch::STATUS_DELIVERED,
                DeliveryDispatch::STATUS_CANCELLED,
            ])],
            'confirmation_note' => ['required', 'string', 'min:3', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($restaurant, $dispatch, $actor, $validated): DeliveryDispatch {
            $lockedRestaurant = Restaurant::query()
                ->whereKey($restaurant->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOrder = $lockedRestaurant->orders()
                ->whereKey($dispatch->order_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedDispatch = $lockedOrder->deliveryDispatches()
                ->whereKey($dispatch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('update', $lockedOrder);

            $allowedTransitions = [
                DeliveryDispatch::STATUS_QUEUED => [
                    DeliveryDispatch::STATUS_ACCEPTED,
                    DeliveryDispatch::STATUS_CANCELLED,
                ],
                DeliveryDispatch::STATUS_ACCEPTED => [
                    DeliveryDispatch::STATUS_PICKED_UP,
                    DeliveryDispatch::STATUS_CANCELLED,
                ],
                DeliveryDispatch::STATUS_PICKED_UP => [
                    DeliveryDispatch::STATUS_DELIVERED,
                ],
            ];
            $currentStatus = $lockedDispatch->status;

            if (! in_array($validated['status'], $allowedTransitions[$currentStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن الانتقال من حالة الإحالة الحالية إلى الحالة المحددة.',
                ]);
            }

            $requiredOrderStatus = match ($validated['status']) {
                DeliveryDispatch::STATUS_ACCEPTED,
                DeliveryDispatch::STATUS_PICKED_UP => 'ready',
                DeliveryDispatch::STATUS_DELIVERED => 'on_way',
                DeliveryDispatch::STATUS_CANCELLED => null,
            };

            if (
                $validated['status'] !== DeliveryDispatch::STATUS_CANCELLED
                && (
                    $lockedOrder->delivery_type !== 'delivery'
                    || $lockedOrder->driver_id !== null
                    || $lockedOrder->status !== $requiredOrderStatus
                )
            ) {
                throw ValidationException::withMessages([
                    'status' => 'حالة الطلب أو تعيين السائق لا يسمح بتحديث إحالة الشركة.',
                ]);
            }

            $timestampColumn = match ($validated['status']) {
                DeliveryDispatch::STATUS_ACCEPTED => 'accepted_at',
                DeliveryDispatch::STATUS_PICKED_UP => 'picked_up_at',
                DeliveryDispatch::STATUS_DELIVERED => 'delivered_at',
                DeliveryDispatch::STATUS_CANCELLED => 'cancelled_at',
            };

            $lockedDispatch->forceFill([
                'status' => $validated['status'],
                $timestampColumn => now(),
            ])->save();

            $lockedDispatch->events()->create([
                'actor_id' => $actor->getKey(),
                'from_status' => $currentStatus,
                'to_status' => $validated['status'],
                'note' => $validated['confirmation_note'],
            ]);

            if ($validated['status'] === DeliveryDispatch::STATUS_PICKED_UP) {
                $lockedOrder->update(['status' => 'on_way']);
            } elseif ($validated['status'] === DeliveryDispatch::STATUS_DELIVERED) {
                $lockedOrder->update(['status' => 'delivered']);
            }

            return $lockedDispatch->load(['provider', 'events.actor']);
        }, 3);
    }

    private function ensureExternalDeliveryEnabled(Restaurant $restaurant): void
    {
        if (! $restaurant->serviceEnabled('delivery')) {
            throw ValidationException::withMessages([
                'provider' => 'خدمة التوصيل غير مفعّلة لهذا المطعم.',
            ]);
        }

        if (! $restaurant->externalDeliveryEnabled()) {
            throw ValidationException::withMessages([
                'provider' => 'التوصيل عبر الشركات الخارجية غير مفعّل لهذا المطعم.',
            ]);
        }
    }

    private function ensureOrderCanBeDispatched(Order $order): void
    {
        if ($order->delivery_type !== 'delivery' || $order->status !== 'ready' || $order->driver_id !== null) {
            throw ValidationException::withMessages([
                'order' => 'لا يمكن إحالة هذا الطلب. يجب أن يكون طلب توصيل جاهزاً وغير مسند إلى سائق داخلي.',
            ]);
        }
    }

    private function ensureProviderIsEligible(Restaurant $restaurant, DeliveryProvider $provider): void
    {
        $eligible = $restaurant->eligibleDeliveryProviders()
            ->whereKey($provider->getKey())
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages([
                'provider' => 'شركة التوصيل غير نشطة أو غير مرتبطة بهذا المطعم أو لا تدعم الإحالة اليدوية.',
            ]);
        }
    }
}
