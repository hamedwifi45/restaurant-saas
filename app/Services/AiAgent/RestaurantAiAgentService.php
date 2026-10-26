<?php

namespace App\Services\AiAgent;

use App\Models\DeliveryProvider;
use App\Models\Order;
use App\Models\PlatformFeature;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\DeliveryPricingService;
use Illuminate\Support\Facades\Http;
use Throwable;

class RestaurantAiAgentService
{
    public function __construct(
        protected DeliveryPricingService $deliveryPricingService,
    ) {}

    /**
     * Sanitize user prompt against prompt-injection and role-escalation attempts.
     */
    public function sanitizePrompt(string $prompt): string
    {
        $cleaned = trim( strip_tags($prompt));
        $cleaned = mb_substr($cleaned, 0, 1200);

        $blockedPatterns = [
            '/ignore\s+(all\s+)?previous\s+instructions/i',
            '/system\s*prompt/i',
            '/act\s+as\s+super[_\s]?admin/i',
            '/bypass\s+tenant/i',
        ];

        foreach ($blockedPatterns as $pattern) {
            $cleaned = (string) preg_replace($pattern, '[filtered]', $cleaned);
        }

        return $cleaned;
    }

    /**
     * Handle an AI assistant request strictly scoped to the caller's role and tenant.
     *
     * @param  array<string, mixed>  $context
     * @return array{
     *   role_scope: string,
     *   reply: string,
     *   tools_invoked: array<int, string>,
     *   structured_data: array<string, mixed>,
     *   requires_confirmation: bool
     * }
     */
    public function respond(
        string $prompt,
        ?User $user = null,
        ?Restaurant $restaurant = null,
        array $context = [],
    ): array {
        $cleanPrompt = $this->sanitizePrompt($prompt);
        $roleScope = $this->resolveRoleScope($user, $restaurant, $context);

        $toolsInvoked = [];
        $structuredData = [];
        $requiresConfirmation = false;

        if ($roleScope === 'super_admin') {
            $toolsInvoked[] = 'platform_subscription_and_delivery_overview';
            $structuredData = $this->executeSuperAdminTools($cleanPrompt);
            $reply = $this->buildSuperAdminReply($cleanPrompt, $structuredData);
        } elseif ($roleScope === 'restaurant_admin' && $restaurant !== null) {
            $toolsInvoked[] = 'tenant_operational_and_financial_analyzer';
            $structuredData = $this->executeRestaurantAdminTools($restaurant, $cleanPrompt);
            $reply = $this->buildRestaurantAdminReply($restaurant, $cleanPrompt, $structuredData);
        } else {
            $toolsInvoked[] = 'customer_menu_and_delivery_concierge';
            $structuredData = $this->executeCustomerTools($restaurant, $cleanPrompt, $context);
            $reply = $this->buildCustomerReply($restaurant, $cleanPrompt, $structuredData);
        }

        // Optional external LLM enrichment if configured in environment
        $llmReply = $this->attemptConfiguredLlmCall($roleScope, $cleanPrompt, $structuredData, $reply);

        return [
            'role_scope' => $roleScope,
            'reply' => $llmReply ?? $reply,
            'tools_invoked' => $toolsInvoked,
            'structured_data' => $structuredData,
            'requires_confirmation' => $requiresConfirmation,
        ];
    }

    /**
     * Determine strict role scope; never trust client-supplied role without verifying $user.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolveRoleScope(?User $user, ?Restaurant $restaurant, array $context = []): string
    {
        if ($user !== null && $user->role === 'super_admin') {
            return 'super_admin';
        }

        if ($user !== null && in_array($user->role, ['restaurant_admin', 'staff'], true)) {
            if ($restaurant !== null && (int) $user->restaurant_id === (int) $restaurant->id) {
                return 'restaurant_admin';
            }
        }

        return 'customer';
    }

    /**
     * Customer-scoped tools: Menu recommendations, order tracking by phone/ID within the current restaurant,
     * and eligible delivery provider quotes.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function executeCustomerTools(?Restaurant $restaurant, string $prompt, array $context): array
    {
        if ($restaurant === null) {
            return ['status' => 'no_restaurant_context'];
        }

        $products = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->orderBy('price')
            ->limit(8)
            ->get(['id', 'name', 'price', 'description', 'preparation_time']);

        $deliveryQuotes = [];
        if (isset($context['latitude'], $context['longitude'])
            && is_numeric($context['latitude'])
            && is_numeric($context['longitude'])) {
            $deliveryQuotes = $this->deliveryPricingService->evaluateEligibleProvidersForCustomer(
                $restaurant,
                (float) $context['latitude'],
                (float) $context['longitude'],
                (float) ($context['subtotal'] ?? 15.0),
            );
        }

        $trackedOrder = null;
        if (! empty($context['order_id'])) {
            $order = Order::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('id', (int) $context['order_id'])
                ->first(['id', 'status', 'order_type', 'total_price', 'estimated_duration_minutes']);

            if ($order !== null) {
                $trackedOrder = $order->toArray();
            }
        }

        return [
            'restaurant_name' => $restaurant->name,
            'can_accept_orders' => $restaurant->canAcceptOrders(),
            'internal_drivers_available' => $restaurant->hasAvailableInternalDrivers(),
            'recommended_products' => $products->toArray(),
            'eligible_delivery_providers' => $deliveryQuotes,
            'tracked_order' => $trackedOrder,
        ];
    }

    /**
     * Restaurant Admin tools: Strictly scoped to $restaurant->id. Never accesses other tenants.
     *
     * @return array<string, mixed>
     */
    protected function executeRestaurantAdminTools(Restaurant $restaurant, string $prompt): array
    {
        $todayOrdersQuery = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('created_at', today());

        $todayOrdersCount = (clone $todayOrdersQuery)->count();
        $todayRevenue = (float) (clone $todayOrdersQuery)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_price');
        $pendingOrdersCount = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['pending', 'preparing'])
            ->count();

        return [
            'restaurant_id' => $restaurant->id,
            'restaurant_name' => $restaurant->name,
            'plan' => $restaurant->plan ?? 'starter',
            'today_orders_count' => $todayOrdersCount,
            'today_revenue' => round($todayRevenue, 2),
            'pending_kitchen_orders' => $pendingOrdersCount,
            'internal_drivers_available' => $restaurant->hasAvailableInternalDrivers(),
            'external_delivery_enabled' => $restaurant->externalDeliveryEnabled(),
        ];
    }

    /**
     * Super Admin (Sham) tools: Platform-wide subscription metrics, delivery providers, and feature catalog.
     *
     * @return array<string, mixed>
     */
    protected function executeSuperAdminTools(string $prompt): array
    {
        return [
            'total_restaurants' => Restaurant::query()->count(),
            'active_restaurants' => Restaurant::query()->where('is_active', true)->count(),
            'pro_restaurants' => Restaurant::query()->where('plan', 'pro')->count(),
            'starter_restaurants' => Restaurant::query()->where('plan', 'starter')->count(),
            'active_delivery_providers' => DeliveryProvider::query()->where('is_active', true)->count(),
            'feature_catalog_count' => count(PlatformFeature::defaultCatalog()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildCustomerReply(?Restaurant $restaurant, string $prompt, array $data): string
    {
        if ($restaurant === null) {
            return 'مرحباً بك! يرجى فتح صفحة المطعم المطلوب لمساعدتك في اختيار الوجبات، حساب رسوم التوصيل حسب موقعك، أو تتبع طلبك.';
        }

        $parts = ["مرحباً بك في {$restaurant->name}!"];

        if (! empty($data['tracked_order'])) {
            $order = $data['tracked_order'];
            $parts[] = "حالة طلبك رقم #{$order['id']} حالياً: ({$order['status']})، الإجمالي: {$order['total_price']} {$restaurant->currency_symbol}.";
        }

        if (! empty($data['recommended_products'])) {
            $topNames = collect($data['recommended_products'])
                ->take(3)
                ->map(fn (array $p): string => "{$p['name']} ({$p['price']} {$restaurant->currency_symbol})")
                ->implode('، ');
            $parts[] = "أبرز الأصناف المتاحة الآن: {$topNames}.";
        }

        if (! empty($data['eligible_delivery_providers'])) {
            $best = $data['eligible_delivery_providers'][0];
            $parts[] = "أفضل خيار توصيل متاح لموقعك: {$best['provider_name']} بتكلفة {$best['fee']} {$restaurant->currency_symbol} ووقت تقديري {$best['duration_minutes']} دقيقة.";
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildRestaurantAdminReply(Restaurant $restaurant, string $prompt, array $data): string
    {
        return sprintf(
            'ملخص عمليات مطعم (%s) اليوم: عدد الطلبات %d بإجمالي مبيعات %0.2f %s، ويوجد حالياً %d طلب قيد التحضير في المطبخ (KDS). حالة السائقين الداخليين: %s.',
            $restaurant->name,
            $data['today_orders_count'] ?? 0,
            $data['today_revenue'] ?? 0.0,
            $restaurant->currency_symbol ?? 'د.أ',
            $data['pending_kitchen_orders'] ?? 0,
            ! empty($data['internal_drivers_available']) ? 'متوفرون' : 'غير متوفرين (يُنصح بتفعيل شركات التوصيل الخارجية)'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildSuperAdminReply(string $prompt, array $data): string
    {
        return sprintf(
            'تقرير المنصة (Super Admin): إجمالي المطاعم %d (منها %d نشط، %d على باقة Pro و%d على باقة Starter). عدد شركات التوصيل النشطة: %d، وعدد الميزات المسعّرة في الكتالوج: %d ميزة.',
            $data['total_restaurants'] ?? 0,
            $data['active_restaurants'] ?? 0,
            $data['pro_restaurants'] ?? 0,
            $data['starter_restaurants'] ?? 0,
            $data['active_delivery_providers'] ?? 0,
            $data['feature_catalog_count'] ?? 0,
        );
    }

    /**
     * Optional call to external LLM API if configured in .env (`AI_AGENT_API_KEY` or `GEMINI_API_KEY`).
     * Never exposes internal keys or cross-tenant records.
     *
     * @param  array<string, mixed>  $structuredData
     */
    protected function attemptConfiguredLlmCall(
        string $roleScope,
        string $cleanPrompt,
        array $structuredData,
        string $fallbackReply,
    ): ?string {
        $apiKey = (string) config('services.ai_agent.key', env('AI_AGENT_API_KEY', ''));
        $endpoint = (string) config('services.ai_agent.endpoint', env('AI_AGENT_ENDPOINT', ''));

        if ($apiKey === '' || $endpoint === '') {
            return null;
        }

        try {
            $response = Http::timeout(6)
                ->withToken($apiKey)
                ->post($endpoint, [
                    'role_scope' => $roleScope,
                    'prompt' => $cleanPrompt,
                    'context_data' => $structuredData,
                ]);

            if ($response->successful() && is_string($response->json('reply'))) {
                return $response->json('reply');
            }
        } catch (Throwable) {
            return $fallbackReply;
        }

        return null;
    }
}
