<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\AiAgent\RestaurantAiAgentService;
use App\Services\DeliveryPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAgentController extends Controller
{
    public function __construct(
        protected RestaurantAiAgentService $aiAgentService,
        protected DeliveryPricingService $deliveryPricingService,
    ) {}

    /**
     * Role-scoped AI Assistant endpoint with strict server-side tenant & feature enforcement.
     */
    public function assist(Request $request, ?string $slug = null): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:1200'],
            'restaurant_slug' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'order_id' => ['nullable', 'integer'],
            'tracking_code' => ['nullable', 'string', 'max:64'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'history' => ['nullable', 'array', 'max:10'],
            'execute_action' => ['nullable', 'string', 'max:64'],
            'action_payload' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $effectiveSlug = $slug ?? ($validated['restaurant_slug'] ?? null);

        $restaurant = null;
        if (is_string($effectiveSlug) && $effectiveSlug !== '') {
            $restaurant = Restaurant::query()
                ->where('slug', $effectiveSlug)
                ->where('is_active', true)
                ->firstOrFail();
        } elseif ($user?->restaurant_id) {
            $restaurant = Restaurant::query()
                ->whereKey($user->restaurant_id)
                ->where('is_active', true)
                ->first();
        }

        // Prevent cross-tenant access: an authenticated restaurant staff/admin cannot query another restaurant's admin data
        if ($user !== null
            && $user->role !== 'super_admin'
            && $user->restaurant_id !== null
            && $restaurant !== null
            && (int) $user->restaurant_id !== (int) $restaurant->id
        ) {
            abort(403, 'لا تملك صلاحية الوصول إلى بيانات هذا المطعم.');
        }

        // Enforce server-side feature check when tied to a restaurant
        if ($restaurant !== null && ($user === null || $user->role !== 'super_admin')) {
            abort_unless(
                $restaurant->hasFeature('ai_assistant'),
                403,
                'ميزة الوكيل الذكي غير مفعّلة في باقة هذا المطعم حالياً.',
            );
        }

        // Handle confirmed Restaurant Admin operational action if requested
        if (! empty($validated['execute_action'])) {
            abort_if(
                $user === null || $restaurant === null,
                403,
                'غير مصرح لك بتنفيذ هذا الإجراء التشغيلي.',
            );

            $actionResult = $this->aiAgentService->executeConfirmedAdminAction(
                $restaurant,
                $user,
                (string) $validated['execute_action'],
                (array) ($validated['action_payload'] ?? []),
            );

            return response()->json([
                'role_scope' => $this->aiAgentService->resolveRoleScope($user, $restaurant),
                'intent' => 'operational_command_executed',
                'confidence' => 1.0,
                'reply' => $actionResult['message'],
                'tools_invoked' => ['admin_confirmed_operational_mutation'],
                'structured_data' => [
                    'restaurant_id' => $restaurant->id,
                    'action_executed' => $actionResult['success'],
                    'updated_state' => $actionResult['updated_state'],
                ],
                'action_cards' => [],
                'suggested_prompts' => [
                    'ملخص عمليات المطعم اليوم',
                    'تشخيص شاشة المطبخ (KDS) والطلبات النشطة',
                ],
                'requires_confirmation' => false,
                'pending_action' => null,
            ], $actionResult['success'] ? 200 : 403);
        }

        $result = $this->aiAgentService->respond(
            prompt: (string) $validated['prompt'],
            user: $user,
            restaurant: $restaurant,
            context: [
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'subtotal' => $validated['subtotal'] ?? 0,
                'order_id' => $validated['order_id'] ?? null,
                'tracking_code' => $validated['tracking_code'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'budget' => $validated['budget'] ?? null,
                'party_size' => $validated['party_size'] ?? null,
                'history' => $validated['history'] ?? [],
            ],
        );

        return response()->json($result);
    }

    /**
     * Evaluate eligible external delivery providers and internal driver availability for a customer's location.
     */
    public function evaluateDeliveryOptions(Request $request, string $slug): JsonResponse
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless($restaurant->serviceEnabled('delivery'), 403, 'خدمة التوصيل غير مفعّلة لهذا المطعم.');

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];
        $subtotal = (float) ($validated['subtotal'] ?? 0.0);

        $hasInternalDrivers = $restaurant->hasAvailableInternalDrivers();
        $internalQuote = null;

        if ($hasInternalDrivers && $this->deliveryPricingService->hasValidCoordinates((float) $restaurant->latitude, (float) $restaurant->longitude)) {
            $route = $this->deliveryPricingService->resolveRoadRoute(
                (float) $restaurant->latitude,
                (float) $restaurant->longitude,
                $lat,
                $lng,
                (float) config('services.osrm.detour_factor', 1.0),
            );

            $maxRadius = (float) ($restaurant->delivery_radius_km ?? 10.0);
            if ($route['distance_km'] <= $maxRadius) {
                $internalQuote = [
                    'mode' => 'internal_driver',
                    'label' => 'توصيل خاص بالمطعم',
                    'fee' => $this->deliveryPricingService->feeForDistance(
                        $route['distance_km'],
                        (float) ($restaurant->base_delivery_fee ?? 2.0),
                        (float) ($restaurant->base_delivery_km ?? 2.0),
                        (float) ($restaurant->per_km_delivery_fee ?? 0.5),
                    ),
                    'distance_km' => $route['distance_km'],
                    'duration_minutes' => $route['duration_minutes'] + 15,
                    'route_source' => $route['source'],
                ];
            }
        }

        $externalProviders = $this->deliveryPricingService->evaluateEligibleProvidersForCustomer(
            $restaurant,
            $lat,
            $lng,
            $subtotal,
        );

        $requiresExternal = $internalQuote === null && $restaurant->externalDeliveryEnabled();

        return response()->json([
            'internal_driver_available' => $internalQuote !== null,
            'internal_quote' => $internalQuote,
            'requires_external_provider' => $requiresExternal,
            'eligible_providers' => $externalProviders,
            'customer_can_choose_external_provider' => $internalQuote === null && count($externalProviders) > 0,
            'eligible_external_providers' => $externalProviders,
        ]);
    }
}
