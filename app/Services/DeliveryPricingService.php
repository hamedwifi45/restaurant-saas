<?php

namespace App\Services;

use App\Models\DeliveryProvider;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliveryPricingService
{
    public const DEFAULT_ROAD_DETOUR_FACTOR = 1.28;

    /**
     * Validate geographic coordinates strictly.
     */
    public function hasValidCoordinates(
        ?float $latitude,
        ?float $longitude,
    ): bool {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        if (! is_finite($latitude) || ! is_finite($longitude)) {
            return false;
        }

        if ($latitude < -90.0 || $latitude > 90.0 || $longitude < -180.0 || $longitude > 180.0) {
            return false;
        }

        return ! (abs($latitude) < 0.00001 && abs($longitude) < 0.00001);
    }

    /**
     * Straight-line Haversine distance in kilometers.
     */
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

    /**
     * Calculate road route distance & estimated duration using OSRM (with alternatives=true to pick
     * the shortest valid road path) or fall back to Haversine * roadDetourFactor when offline.
     *
     * @return array{distance_km: float, straight_line_km: float, duration_minutes: int, source: string}
     */
    public function resolveRoadRoute(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
        float $roadDetourFactor = self::DEFAULT_ROAD_DETOUR_FACTOR,
    ): array {
        $straightLineKm = $this->distanceInKilometers(
            $fromLatitude,
            $fromLongitude,
            $toLatitude,
            $toLongitude,
        );

        $cacheKey = sprintf(
            'delivery_route_v2:%0.4f,%0.4f:%0.4f,%0.4f',
            $fromLatitude,
            $fromLongitude,
            $toLatitude,
            $toLongitude,
        );

        $osrmBaseUrl = rtrim((string) config('services.osrm.url', env('OSRM_ROUTING_URL', '')), '/');

        if ($osrmBaseUrl !== '') {
            try {
                /** @var array{distance_km: float, straight_line_km: float, duration_minutes: int, source: string}|null $cached */
                $cached = Cache::remember($cacheKey, now()->addMinutes(30), function () use (
                    $osrmBaseUrl,
                    $fromLatitude,
                    $fromLongitude,
                    $toLatitude,
                    $toLongitude,
                    $straightLineKm
                ): ?array {
                    $url = sprintf(
                        '%s/route/v1/driving/%0.6f,%0.6f;%0.6f,%0.6f?overview=false&alternatives=true',
                        $osrmBaseUrl,
                        $fromLongitude,
                        $fromLatitude,
                        $toLongitude,
                        $toLatitude,
                    );

                    $response = Http::timeout(3)->acceptJson()->get($url);
                    if (! $response->successful()) {
                        return null;
                    }

                    $routes = $response->json('routes');
                    if (! is_array($routes) || $routes === []) {
                        return null;
                    }

                    // Pick the shortest valid driving route (preventing inflated fees from longer highway detours)
                    $shortestRoute = collect($routes)
                        ->filter(fn (mixed $r): bool => is_array($r) && isset($r['distance']) && (float) $r['distance'] > 0)
                        ->sortBy(fn (array $r): float => (float) $r['distance'])
                        ->first();

                    if (! is_array($shortestRoute)) {
                        return null;
                    }

                    $distanceKm = round(((float) $shortestRoute['distance']) / 1000, 2);
                    $durationMinutes = max(3, (int) ceil(((float) ($shortestRoute['duration'] ?? 0)) / 60));

                    return [
                        'distance_km' => $distanceKm,
                        'straight_line_km' => round($straightLineKm, 2),
                        'duration_minutes' => $durationMinutes,
                        'source' => 'osrm_road_shortest',
                    ];
                });

                if (is_array($cached)) {
                    return $cached;
                }
            } catch (Throwable) {
                // Graceful fallback to road detour factor below
            }
        }

        $effectiveFactor = max(1.0, min(2.5, $roadDetourFactor));
        $estimatedRoadKm = round($straightLineKm * $effectiveFactor, 2);
        $estimatedMinutes = max(5, (int) ceil(($estimatedRoadKm / 30.0) * 60) + 5);

        return [
            'distance_km' => $estimatedRoadKm,
            'straight_line_km' => round($straightLineKm, 2),
            'duration_minutes' => $estimatedMinutes,
            'source' => 'haversine_detour_fallback',
        ];
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

    /**
     * Check if a point (lat, lng) lies inside a polygon using the Ray-Casting algorithm.
     *
     * @param  array<int, array{lat?: float|int, lng?: float|int, 0?: float|int, 1?: float|int}>  $polygon
     */
    public function pointInPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $normalized = [];
        foreach ($polygon as $vertex) {
            if (! is_array($vertex)) {
                continue;
            }
            $vLat = $vertex['lat'] ?? $vertex[0] ?? null;
            $vLng = $vertex['lng'] ?? $vertex[1] ?? null;
            if (is_numeric($vLat) && is_numeric($vLng)) {
                $normalized[] = [(float) $vLat, (float) $vLng];
            }
        }

        $count = count($normalized);
        if ($count < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$latI, $lngI] = $normalized[$i];
            [$latJ, $lngJ] = $normalized[$j];

            $intersects = (($lngI > $longitude) !== ($lngJ > $longitude))
                && ($latitude < ($latJ - $latI) * ($longitude - $lngI) / (($lngJ - $lngI) ?: 1e-12) + $latI);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Check if a point falls inside a single zone definition (supports 'circle' or 'polygon').
     *
     * @param  array<string, mixed>  $zone
     */
    public function pointMatchesZone(float $latitude, float $longitude, array $zone): bool
    {
        $type = strtolower((string) ($zone['type'] ?? 'circle'));

        if ($type === 'polygon' && is_array($zone['coordinates'] ?? null)) {
            return $this->pointInPolygon($latitude, $longitude, $zone['coordinates']);
        }

        $centerLat = isset($zone['center_lat']) ? (float) $zone['center_lat'] : null;
        $centerLng = isset($zone['center_lng']) ? (float) $zone['center_lng'] : null;
        $radiusKm = isset($zone['radius_km']) ? (float) $zone['radius_km'] : 0.0;

        if ($centerLat === null || $centerLng === null || $radiusKm <= 0) {
            return false;
        }

        return $this->distanceInKilometers($centerLat, $centerLng, $latitude, $longitude) <= $radiusKm;
    }

    /**
     * Evaluate whether a customer location & order subtotal is eligible for a given DeliveryProvider,
     * and compute the exact delivery fee and ETA.
     *
     * @return array{
     *   eligible: bool,
     *   reason: string|null,
     *   fee: float|null,
     *   distance_km: float|null,
     *   duration_minutes: int|null,
     *   route_source: string|null,
     *   matched_zone: string|null
     * }
     */
    public function evaluateProviderQuote(
        DeliveryProvider $provider,
        Restaurant $restaurant,
        float $customerLatitude,
        float $customerLongitude,
        float $orderSubtotal = 0.0,
    ): array {
        if (! $provider->is_active) {
            return [
                'eligible' => false,
                'reason' => 'provider_inactive',
                'fee' => null,
                'distance_km' => null,
                'duration_minutes' => null,
                'route_source' => null,
                'matched_zone' => null,
            ];
        }

        if (! $this->hasValidCoordinates((float) $restaurant->latitude, (float) $restaurant->longitude)
            || ! $this->hasValidCoordinates($customerLatitude, $customerLongitude)) {
            return [
                'eligible' => false,
                'reason' => 'invalid_coordinates',
                'fee' => null,
                'distance_km' => null,
                'duration_minutes' => null,
                'route_source' => null,
                'matched_zone' => null,
            ];
        }

        $minOrderAmount = (float) ($provider->min_order_amount ?? 0);
        if ($minOrderAmount > 0 && $orderSubtotal < $minOrderAmount) {
            return [
                'eligible' => false,
                'reason' => 'below_min_order_amount',
                'fee' => null,
                'distance_km' => null,
                'duration_minutes' => null,
                'route_source' => null,
                'matched_zone' => null,
            ];
        }

        // 1. Check blocked zones first (Explicit Exclusion)
        $blockedZones = is_array($provider->blocked_zones) ? $provider->blocked_zones : [];
        foreach ($blockedZones as $blockedZone) {
            if (is_array($blockedZone) && $this->pointMatchesZone($customerLatitude, $customerLongitude, $blockedZone)) {
                return [
                    'eligible' => false,
                    'reason' => 'in_blocked_zone',
                    'fee' => null,
                    'distance_km' => null,
                    'duration_minutes' => null,
                    'route_source' => null,
                    'matched_zone' => (string) ($blockedZone['name'] ?? 'منطقة محظورة'),
                ];
            }
        }

        // 2. Check allowed zones (if configured)
        $allowedZones = is_array($provider->allowed_zones) ? $provider->allowed_zones : [];
        $matchedZone = null;
        $zoneFlatFee = null;

        if ($allowedZones !== []) {
            foreach ($allowedZones as $zone) {
                if (is_array($zone) && $this->pointMatchesZone($customerLatitude, $customerLongitude, $zone)) {
                    $matchedZone = (string) ($zone['name'] ?? 'نطاق مخدوم');
                    if (isset($zone['flat_fee']) && is_numeric($zone['flat_fee'])) {
                        $zoneFlatFee = round((float) $zone['flat_fee'], 2);
                    }
                    break;
                }
            }

            if ($matchedZone === null) {
                return [
                    'eligible' => false,
                    'reason' => 'outside_allowed_zones',
                    'fee' => null,
                    'distance_km' => null,
                    'duration_minutes' => null,
                    'route_source' => null,
                    'matched_zone' => null,
                ];
            }
        }

        // 3. Calculate road distance & duration
        $detourFactor = (float) ($provider->road_detour_factor ?: self::DEFAULT_ROAD_DETOUR_FACTOR);
        $route = $this->resolveRoadRoute(
            (float) $restaurant->latitude,
            (float) $restaurant->longitude,
            $customerLatitude,
            $customerLongitude,
            $detourFactor,
        );

        $distanceKm = $route['distance_km'];
        $minDistanceKm = (float) ($provider->min_distance_km ?? 0);
        $maxDistanceKm = (float) ($provider->max_distance_km ?? 25);

        if ($distanceKm < $minDistanceKm || ($maxDistanceKm > 0 && $distanceKm > $maxDistanceKm)) {
            return [
                'eligible' => false,
                'reason' => 'distance_out_of_bounds',
                'fee' => null,
                'distance_km' => $distanceKm,
                'duration_minutes' => $route['duration_minutes'],
                'route_source' => $route['source'],
                'matched_zone' => $matchedZone,
            ];
        }

        // 4. Compute Fee according to pricing_model
        $pricingModel = (string) ($provider->pricing_model ?? 'distance_per_km');
        $baseFee = (float) ($provider->base_fee ?? $restaurant->base_delivery_fee ?? 2.00);
        $baseDistanceKm = (float) ($provider->base_distance_km ?? $restaurant->base_delivery_km ?? 2.00);
        $perKmFee = (float) ($provider->per_km_fee ?? $restaurant->per_km_delivery_fee ?? 0.50);

        if ($pricingModel === 'flat') {
            $fee = round($baseFee, 2);
        } elseif ($pricingModel === 'zone_flat' && $zoneFlatFee !== null) {
            $fee = $zoneFlatFee;
        } else {
            $fee = $this->feeForDistance($distanceKm, $baseFee, $baseDistanceKm, $perKmFee);
        }

        $pickupPrepMinutes = (int) ($provider->estimated_pickup_minutes ?? 15);

        return [
            'eligible' => true,
            'reason' => null,
            'fee' => $fee,
            'distance_km' => $distanceKm,
            'duration_minutes' => $pickupPrepMinutes + $route['duration_minutes'],
            'route_source' => $route['source'],
            'matched_zone' => $matchedZone,
        ];
    }

    /**
     * Return all eligible external delivery providers for a restaurant & customer coordinates,
     * sorted by lowest fee then fastest ETA.
     *
     * @return array<int, array<string, mixed>>
     */
    public function evaluateEligibleProvidersForCustomer(
        Restaurant $restaurant,
        float $customerLatitude,
        float $customerLongitude,
        float $orderSubtotal = 0.0,
    ): array {
        if (! $restaurant->externalDeliveryEnabled()) {
            return [];
        }

        $providers = $restaurant->deliveryProviders()
            ->wherePivot('is_enabled', true)
            ->where('delivery_providers.is_active', true)
            ->get();

        $quotes = [];
        foreach ($providers as $provider) {
            $quote = $this->evaluateProviderQuote(
                $provider,
                $restaurant,
                $customerLatitude,
                $customerLongitude,
                $orderSubtotal,
            );

            if ($quote['eligible']) {
                $quotes[] = [
                    'provider_id' => $provider->id,
                    'provider_name' => $provider->name,
                    'fee' => $quote['fee'],
                    'distance_km' => $quote['distance_km'],
                    'duration_minutes' => $quote['duration_minutes'],
                    'route_source' => $quote['route_source'],
                    'matched_zone' => $quote['matched_zone'],
                ];
            }
        }

        usort($quotes, function (array $a, array $b): int {
            $feeCompare = ($a['fee'] ?? 0) <=> ($b['fee'] ?? 0);

            return $feeCompare !== 0
                ? $feeCompare
                : (($a['duration_minutes'] ?? 0) <=> ($b['duration_minutes'] ?? 0));
        });

        return $quotes;
    }
}
