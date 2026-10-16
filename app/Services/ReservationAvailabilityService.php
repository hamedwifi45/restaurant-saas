<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReservationAvailabilityService
{
    /**
     * @return Collection<int, RestaurantTable>
     */
    public function availableTables(
        Restaurant $restaurant,
        CarbonInterface $arrivalAt,
        int $guestCount,
        int $durationMinutes,
        ?int $ignoreOrderId = null,
    ): Collection {
        $features = $restaurant->getExperimentalOrderFeatures();
        $defaultDuration = (int) $features['reservation_duration_minutes'];
        $maximumDuration = max(240, ...($features['reservation_duration_options'] ?: [$defaultDuration]));
        $newReservationEndsAt = $arrivalAt->copy()->addMinutes($durationMinutes);
        $earliestExistingStart = $arrivalAt->copy()->subMinutes($maximumDuration);

        $existingReservations = $restaurant->orders()
            ->where('delivery_type', 'dine_in')
            ->where('status', '!=', 'cancelled')
            ->where(function (Builder $query): void {
                $query->whereNull('reservation_status')
                    ->orWhere('reservation_status', '!=', 'rejected');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'rejected');
            })
            ->whereNotNull('dine_in_arrival_at')
            ->where('dine_in_arrival_at', '>=', $earliestExistingStart)
            ->where('dine_in_arrival_at', '<', $newReservationEndsAt)
            ->when($ignoreOrderId, fn (Builder $query): Builder => $query->where('id', '!=', $ignoreOrderId))
            ->get([
                'id',
                'restaurant_table_id',
                'dine_in_arrival_at',
                'dine_in_duration_minutes',
            ]);

        $conflictingTableIds = $existingReservations
            ->filter(function (Order $order) use ($arrivalAt, $durationMinutes, $defaultDuration): bool {
                $existingStart = $order->dine_in_arrival_at;
                $existingEnd = $existingStart->copy()->addMinutes(
                    $order->dine_in_duration_minutes ?: $defaultDuration,
                );

                return $existingStart < $arrivalAt->copy()->addMinutes($durationMinutes)
                    && $existingEnd > $arrivalAt;
            })
            ->pluck('restaurant_table_id')
            ->filter()
            ->unique()
            ->values();

        $occupiedWalkInTableIds = $restaurant->orders()
            ->where('delivery_type', 'dine_in')
            ->whereNull('dine_in_arrival_at')
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->whereNotNull('restaurant_table_id')
            ->pluck('restaurant_table_id');

        return $restaurant->tables()
            ->where('is_active', true)
            ->where('seats', '>=', $guestCount)
            ->whereNotIn('id', $conflictingTableIds->merge($occupiedWalkInTableIds)->unique())
            ->orderBy('seats')
            ->orderBy('number')
            ->get();
    }
}
