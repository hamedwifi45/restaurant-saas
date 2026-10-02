<?php

namespace App\Services;

class DeliveryPricingService
{
    public function distanceInKilometers(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float {
        $latitudeDifference = deg2rad($toLatitude - $fromLatitude);
        $longitudeDifference = deg2rad($toLongitude - $fromLongitude);

        $haversine = sin($latitudeDifference / 2) ** 2
            + cos(deg2rad($fromLatitude))
            * cos(deg2rad($toLatitude))
            * sin($longitudeDifference / 2) ** 2;
        $haversine = min(1, max(0, $haversine));

        return 6371 * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));
    }

    public function feeForDistance(
        float $distanceInKilometers,
        float $baseFee,
        float $baseDistanceInKilometers,
        float $feePerAdditionalKilometer,
    ): float {
        $additionalKilometers = max(0, $distanceInKilometers - $baseDistanceInKilometers);

        return round($baseFee + ($additionalKilometers * $feePerAdditionalKilometer), 2);
    }
}
