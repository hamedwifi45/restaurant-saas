<?php

namespace App\Services\AiAgent;

use App\Models\Coupon;
use App\Models\DeliveryDispatch;
use App\Models\DeliveryProvider;
use App\Models\Expense;
use App\Models\LocalOrderSyncOutbox;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\PlatformFeature;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\DeliveryPricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class RestaurantAiAgentService
{
    protected AiAgentKnowledgeTrainer $knowledgeTrainer;

    public function __construct(
        protected DeliveryPricingService $deliveryPricingService,
        ?AiAgentKnowledgeTrainer $knowledgeTrainer = null,
    ) {
        $this->knowledgeTrainer = $knowledgeTrainer ?? new AiAgentKnowledgeTrainer;
    }

    /**
     * Sanitize user prompt against prompt-injection and role-escalation attempts.
     */
    public function sanitizePrompt(string $prompt): string
    {
        $cleaned = trim(strip_tags($prompt));
        $cleaned = mb_substr($cleaned, 0, 1200);

        $blockedPatterns = [
            '/ignore\s+(all\s+)?previous\s+instructions/i',
            '/disregard\s+(all\s+)?(prior|previous)\s+instructions/i',
            '/system\s*prompt/i',
            '/act\s+as\s+super[_\s]?admin/i',
            '/bypass\s+tenant/i',
            '/تجاهل\s+(كل\s+|جميع\s+)?التعليمات/u',
            '/صلاحيات\s+مدير\s+المنصة/u',
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
     *   intent: string,
     *   confidence: float,
     *   reply: string,
     *   tools_invoked: array<int, string>,
     *   structured_data: array<string, mixed>,
     *   action_cards: array<int, array<string, mixed>>,
     *   suggested_prompts: array<int, string>,
     *   requires_confirmation: bool,
     *   pending_action: array<string, mixed>|null
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
        $nlu = $this->knowledgeTrainer->classifyAndExtract($cleanPrompt, $roleScope, $context);

        $intent = $nlu['intent'];
        $confidence = $nlu['confidence'];
        $entities = $nlu['entities'];

        $toolsInvoked = ['nlu_domain_intent_classifier'];
        $structuredData = [];
        $actionCards = [];
        $requiresConfirmation = false;
        $pendingAction = null;

        if ($roleScope === 'super_admin') {
            $toolsInvoked[] = 'platform_subscription_and_delivery_overview';
            $structuredData = $this->executeSuperAdminTools($cleanPrompt, $intent);
            $reply = $this->buildSuperAdminReply($cleanPrompt, $structuredData);
        } elseif ($roleScope === 'restaurant_admin' && $restaurant !== null) {
            $toolsInvoked[] = 'tenant_operational_and_financial_analyzer';
            $toolsInvoked[] = "admin_intent_{$intent}";
            $structuredData = $this->executeRestaurantAdminTools($restaurant, $cleanPrompt, $intent, $entities);
            $actionCards = $this->buildRestaurantAdminActionCards($restaurant, $intent, $structuredData);

            if (! empty($structuredData['pending_action'])) {
                $requiresConfirmation = true;
                $pendingAction = $structuredData['pending_action'];
            }

            $reply = $this->buildRestaurantAdminReply($restaurant, $cleanPrompt, $intent, $structuredData);
        } else {
            $toolsInvoked[] = 'customer_menu_and_delivery_concierge';
            $toolsInvoked[] = "customer_intent_{$intent}";
            $structuredData = $this->executeCustomerTools($restaurant, $cleanPrompt, $context, $intent, $entities);
            $actionCards = $this->buildCustomerActionCards($restaurant, $intent, $structuredData);
            $reply = $this->buildCustomerReply($restaurant, $cleanPrompt, $intent, $structuredData, $entities);
        }

        // Optional external LLM enrichment (Gemini or OpenAI-compatible) if configured in environment
        $llmReply = $this->attemptConfiguredLlmCall(
            $roleScope,
            $restaurant,
            $intent,
            $cleanPrompt,
            $structuredData,
            $reply,
            is_array($context['history'] ?? null) ? $context['history'] : [],
        );

        return [
            'role_scope' => $roleScope,
            'intent' => $intent,
            'confidence' => $confidence,
            'reply' => $llmReply ?? $reply,
            'tools_invoked' => $toolsInvoked,
            'structured_data' => $structuredData,
            'action_cards' => $actionCards,
            'suggested_prompts' => $this->knowledgeTrainer->getSuggestedPrompts($roleScope, $intent),
            'requires_confirmation' => $requiresConfirmation,
            'pending_action' => $pendingAction,
        ];
    }

    /**
     * Execute a confirmed operational command for a Restaurant Admin strictly within their own tenant.
     *
     * @param  array<string, mixed>  $actionPayload
     * @return array{success: bool, message: string, updated_state: array<string, mixed>}
     */
    public function executeConfirmedAdminAction(
        Restaurant $restaurant,
        User $user,
        string $actionType,
        array $actionPayload = [],
    ): array {
        $roleScope = $this->resolveRoleScope($user, $restaurant);
        if (! in_array($roleScope, ['restaurant_admin', 'super_admin'], true)) {
            return [
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذا الإجراء التشغيلي.',
                'updated_state' => [],
            ];
        }

        if ($actionType === 'pause_accepting_orders') {
            $restaurant->update(['is_accepting_orders' => false]);

            return [
                'success' => true,
                'message' => "تم إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة) في {$restaurant->name} بنجاح.",
                'updated_state' => ['is_accepting_orders' => false],
            ];
        }

        if ($actionType === 'resume_accepting_orders') {
            $restaurant->update(['is_accepting_orders' => true]);

            return [
                'success' => true,
                'message' => "تم تفعيل استقبال الطلبات في {$restaurant->name} بنجاح.",
                'updated_state' => ['is_accepting_orders' => true],
            ];
        }

        if ($actionType === 'toggle_product_availability' && ! empty($actionPayload['product_id'])) {
            $product = Product::query()
                ->where('restaurant_id', $restaurant->id)
                ->whereKey((int) $actionPayload['product_id'])
                ->first();

            if ($product === null) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على الصنف المطلوب ضمن قائمة هذا المطعم.',
                    'updated_state' => [],
                ];
            }

            $newStatus = isset($actionPayload['is_available'])
                ? (bool) $actionPayload['is_available']
                : ! $product->is_available;

            $product->update(['is_available' => $newStatus]);

            return [
                'success' => true,
                'message' => sprintf(
                    'تم تحديث حالة الصنف (%s) إلى: %s.',
                    $product->name,
                    $newStatus ? 'متوفر للطلب' : 'غير متوفر مؤقتاً'
                ),
                'updated_state' => [
                    'product_id' => $product->id,
                    'is_available' => $newStatus,
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'نوع الإجراء المطلوب غير مدعوم.',
            'updated_state' => [],
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
     * Customer-scoped tools: Menu recommendations, budget combo builder, active coupons/offers,
     * table reservation rules, order tracking within the current restaurant, and eligible delivery quotes.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    protected function executeCustomerTools(
        ?Restaurant $restaurant,
        string $prompt,
        array $context,
        string $intent = 'menu_recommendation',
        array $entities = [],
    ): array {
        if ($restaurant === null) {
            return ['status' => 'no_restaurant_context'];
        }

        $currency = $restaurant->currency_symbol ?: 'ل.س';

        $allAvailableProducts = Product::query()
            ->with('category:id,name')
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->orderByDesc('is_featured')
            ->orderBy('price')
            ->limit(30)
            ->get(['id', 'category_id', 'name', 'slug', 'price', 'old_price', 'description', 'is_featured', 'image']);

        // Filter products by taste/category keywords if customer mentioned any
        $tasteKeywords = $entities['dietary_or_taste'] ?? [];
        $filteredProducts = $allAvailableProducts;

        if (! empty($tasteKeywords)) {
            $normalizedPrompt = $this->knowledgeTrainer->normalizeArabicText($prompt);
            $matched = $allAvailableProducts->filter(function (Product $product) use ($normalizedPrompt): bool {
                $haystack = $this->knowledgeTrainer->normalizeArabicText(
                    $product->name.' '.($product->description ?? '').' '.($product->category?->name ?? '')
                );
                foreach (explode(' ', $normalizedPrompt) as $token) {
                    if (mb_strlen($token) >= 3 && str_contains($haystack, $token)) {
                        return true;
                    }
                }

                return false;
            });

            if ($matched->isNotEmpty()) {
                $filteredProducts = $matched->values();
            }
        }

        $recommendedProducts = $filteredProducts->take(8)->map(fn (Product $p): array => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'category' => $p->category?->name,
            'price' => (float) $p->price,
            'old_price' => $p->old_price ? (float) $p->old_price : null,
            'description' => $p->description,
            'is_featured' => (bool) $p->is_featured,
        ])->values()->all();

        // Smart Budget & Party Size Combo Builder
        $suggestedCombo = null;
        $budget = $entities['budget'] ?? null;
        $partySize = $entities['party_size'] ?? null;

        if (($intent === 'budget_meal_planner' || $budget !== null || $partySize !== null) && $allAvailableProducts->isNotEmpty()) {
            $targetCount = max(1, (int) ($partySize ?? 2));
            $comboItems = [];
            $runningTotal = 0.0;
            $sortedByPrice = $allAvailableProducts->sortBy('price')->values();

            for ($i = 0; $i < $targetCount; $i++) {
                $candidate = $sortedByPrice[$i % $sortedByPrice->count()];
                $price = (float) $candidate->price;
                if ($budget !== null && ($runningTotal + $price) > (float) $budget && ! empty($comboItems)) {
                    break;
                }
                $runningTotal += $price;
                $comboItems[] = [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'price' => $price,
                ];
            }

            if (! empty($comboItems)) {
                $suggestedCombo = [
                    'party_size' => $targetCount,
                    'target_budget' => $budget,
                    'total_price' => round($runningTotal, 2),
                    'within_budget' => $budget === null || $runningTotal <= (float) $budget,
                    'items' => $comboItems,
                ];
            }
        }

        // Active Offers & Coupons
        $activeCoupons = Coupon::query()
            ->where('restaurant_id', $restaurant->id)
            ->active()
            ->limit(5)
            ->get(['id', 'code', 'name', 'type', 'value', 'min_order_amount', 'ends_at'])
            ->map(fn (Coupon $c): array => [
                'code' => $c->code,
                'name' => $c->name,
                'label' => $c->getDiscountLabel(),
                'min_order_amount' => (float) $c->min_order_amount,
            ])
            ->all();

        $activeOffers = Offer::query()
            ->where('restaurant_id', $restaurant->id)
            ->active()
            ->limit(5)
            ->get(['id', 'title', 'description', 'type', 'value', 'min_order_amount'])
            ->map(fn (Offer $o): array => [
                'title' => $o->title,
                'description' => $o->description,
                'label' => $o->getDiscountLabel(),
                'min_order_amount' => (float) $o->min_order_amount,
            ])
            ->all();

        // Delivery Quotes (Internal Driver + Eligible External Providers)
        $deliveryQuotes = [];
        $internalDeliveryQuote = null;
        $hasInternalDrivers = $restaurant->hasAvailableInternalDrivers();

        if (isset($context['latitude'], $context['longitude'])
            && is_numeric($context['latitude'])
            && is_numeric($context['longitude'])) {
            $custLat = (float) $context['latitude'];
            $custLng = (float) $context['longitude'];
            $subtotal = (float) ($context['subtotal'] ?? 15.0);

            if ($hasInternalDrivers && $this->deliveryPricingService->hasValidCoordinates((float) $restaurant->latitude, (float) $restaurant->longitude)) {
                $route = $this->deliveryPricingService->resolveRoadRoute(
                    (float) $restaurant->latitude,
                    (float) $restaurant->longitude,
                    $custLat,
                    $custLng,
                    (float) config('services.osrm.detour_factor', 1.0),
                );

                $maxRadius = (float) ($restaurant->delivery_radius_km ?? 10.0);
                if ($route['distance_km'] <= $maxRadius) {
                    $internalDeliveryQuote = [
                        'mode' => 'internal_driver',
                        'provider_name' => 'مندوب المطعم الخاص',
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

            $deliveryQuotes = $this->deliveryPricingService->evaluateEligibleProvidersForCustomer(
                $restaurant,
                $custLat,
                $custLng,
                $subtotal,
            );
        }

        // Order Tracking by Order ID or Tracking Code within this tenant
        $trackedOrder = null;
        $orderIdToFind = $entities['order_id'] ?? ($context['order_id'] ?? null);
        $trackingCodeToFind = $entities['tracking_code'] ?? ($context['tracking_code'] ?? null);

        if (! empty($orderIdToFind) || ! empty($trackingCodeToFind)) {
            $orderQuery = Order::query()
                ->with('deliveryProvider:id,name')
                ->where('restaurant_id', $restaurant->id);

            if (! empty($trackingCodeToFind)) {
                $orderQuery->where('tracking_code', (string) $trackingCodeToFind);
            } else {
                $orderQuery->where('id', (int) $orderIdToFind);
            }

            $order = $orderQuery->first([
                'id',
                'tracking_code',
                'status',
                'delivery_type',
                'total_amount',
                'final_amount',
                'estimated_duration_minutes',
                'delivery_provider_id',
            ]);

            if ($order !== null) {
                $totalPrice = (float) ($order->final_amount ?: $order->total_amount ?: 0);
                $trackedOrder = [
                    'id' => $order->id,
                    'tracking_code' => $order->tracking_code,
                    'status' => $order->status,
                    'status_label' => $this->translateOrderStatus((string) $order->status),
                    'order_type' => $order->delivery_type ?? 'delivery',
                    'total_price' => $totalPrice,
                    'estimated_duration_minutes' => $order->estimated_duration_minutes,
                    'delivery_provider_name' => $order->deliveryProvider?->name,
                ];
            }
        }

        // Table Reservation Summary
        $activeTablesCount = RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->count();
        $maxTableSeats = (int) RestaurantTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->max('seats');

        return [
            'restaurant_name' => $restaurant->name,
            'currency' => $currency,
            'can_accept_orders' => $restaurant->canAcceptOrders(),
            'opening_time' => $restaurant->opening_time,
            'closing_time' => $restaurant->closing_time,
            'phone' => $restaurant->phone,
            'address' => $restaurant->address,
            'internal_drivers_available' => $hasInternalDrivers,
            'internal_delivery_quote' => $internalDeliveryQuote,
            'recommended_products' => $recommendedProducts,
            'suggested_combo' => $suggestedCombo,
            'active_coupons' => $activeCoupons,
            'active_offers' => $activeOffers,
            'eligible_delivery_providers' => $deliveryQuotes,
            'tracked_order' => $trackedOrder,
            'tables_summary' => [
                'active_tables_count' => $activeTablesCount,
                'max_seats' => $maxTableSeats,
                'dine_in_enabled' => $restaurant->serviceEnabled('dine_in'),
                'reservations_enabled' => $restaurant->serviceEnabled('table_reservations'),
            ],
        ];
    }

    /**
     * Restaurant Admin tools: Strictly scoped to $restaurant->id. Never accesses other tenants.
     * Computes live sales, expenses, net profit, menu engineering, KDS bottlenecks, and delivery fleet status.
     *
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    protected function executeRestaurantAdminTools(
        Restaurant $restaurant,
        string $prompt,
        string $intent = 'daily_operations_summary',
        array $entities = [],
    ): array {
        $currency = $restaurant->currency_symbol ?: 'ل.س';

        // 1. Today's Orders & Revenue
        $todayOrdersQuery = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('created_at', today());

        $todayOrdersCount = (clone $todayOrdersQuery)->count();
        $todayNonCancelledQuery = (clone $todayOrdersQuery)->whereNotIn('status', ['cancelled']);
        $todayRevenue = (float) (clone $todayNonCancelledQuery)
            ->selectRaw('COALESCE(SUM(COALESCE(final_amount, total_amount, 0)), 0) as rev')
            ->value('rev');

        $nonCancelledCount = (clone $todayNonCancelledQuery)->count();
        $todayAverageOrder = $nonCancelledCount > 0 ? round($todayRevenue / $nonCancelledCount, 2) : 0.0;

        // 2. Monthly Financial & Expense Analysis (Revenue vs Expenses = Net Profit)
        $monthStart = now()->startOfMonth();
        $monthRevenue = (float) Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('created_at', '>=', $monthStart)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('COALESCE(SUM(COALESCE(final_amount, total_amount, 0)), 0) as rev')
            ->value('rev');

        $monthExpenses = (float) Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('spent_at', '>=', $monthStart->toDateString())
            ->sum('amount');

        $todayExpenses = (float) Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('spent_at', today())
            ->sum('amount');

        $topExpenseCategories = Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('spent_at', '>=', $monthStart->toDateString())
            ->select('category', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->limit(3)
            ->get()
            ->map(fn ($row): array => [
                'category' => $row->category ?: 'عام',
                'total' => round((float) $row->total_amount, 2),
            ])
            ->all();

        $monthNetProfit = round($monthRevenue - $monthExpenses, 2);
        $todayNetProfit = round($todayRevenue - $todayExpenses, 2);

        // 3. Kitchen (KDS) & Local Offline Outbox Diagnostics
        $pendingOrdersCount = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['pending', 'preparing'])
            ->count();

        $readyOrdersCount = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'ready')
            ->count();

        $delayedKitchenOrdersCount = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', ['pending', 'confirmed', 'preparing'])
            ->where('created_at', '<=', now()->subMinutes(20))
            ->count();

        $pendingLocalOutboxCount = LocalOrderSyncOutbox::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('sync_status', '!=', 'synced')
            ->count();

        // 4. Menu Engineering (Top Selling Products & Unavailable Products)
        $topSellingProducts = Order_item::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.restaurant_id', $restaurant->id)
            ->where('orders.status', '!=', 'cancelled')
            ->select(
                'order_items.product_id',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.total) as total_sales')
            )
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'product_id' => $row->product_id,
                'name' => $row->product_name,
                'qty' => (int) $row->total_qty,
                'revenue' => round((float) $row->total_sales, 2),
            ])
            ->all();

        $unavailableProductsCount = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', false)
            ->count();

        // 5. Delivery Fleet & External Dispatches
        $externalDispatchesToday = DeliveryDispatch::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('created_at', today())
            ->count();

        $failedDispatchesToday = DeliveryDispatch::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('created_at', today())
            ->where('status', DeliveryDispatch::STATUS_FAILED)
            ->count();

        // 6. Active Promotions
        $activeCouponsCount = Coupon::query()
            ->where('restaurant_id', $restaurant->id)
            ->active()
            ->count();

        // 7. Operational Command Detection (Requires Admin Confirmation)
        $pendingAction = null;
        if (! empty($entities['requested_action'])) {
            $actionType = (string) $entities['requested_action'];
            $pendingAction = [
                'action_type' => $actionType,
                'label' => $actionType === 'pause_accepting_orders'
                    ? 'تأكيد إيقاف استقبال الطلبات مؤقتاً (وضع الزحمة)'
                    : 'تأكيد إعادة تفعيل استقبال الطلبات',
                'payload' => [],
            ];
        }

        return [
            'restaurant_id' => $restaurant->id,
            'restaurant_name' => $restaurant->name,
            'currency' => $currency,
            'plan' => $restaurant->plan ?? 'starter',
            'is_accepting_orders' => (bool) $restaurant->is_accepting_orders,
            'today_orders_count' => $todayOrdersCount,
            'today_revenue' => round($todayRevenue, 2),
            'today_expenses' => round($todayExpenses, 2),
            'today_net_profit' => $todayNetProfit,
            'today_average_order' => $todayAverageOrder,
            'month_revenue' => round($monthRevenue, 2),
            'month_expenses' => round($monthExpenses, 2),
            'month_net_profit' => $monthNetProfit,
            'top_expense_categories' => $topExpenseCategories,
            'pending_kitchen_orders' => $pendingOrdersCount,
            'ready_kitchen_orders' => $readyOrdersCount,
            'delayed_kitchen_orders' => $delayedKitchenOrdersCount,
            'pending_local_outbox' => $pendingLocalOutboxCount,
            'top_selling_products' => $topSellingProducts,
            'unavailable_products_count' => $unavailableProductsCount,
            'internal_drivers_available' => $restaurant->hasAvailableInternalDrivers(),
            'external_delivery_enabled' => $restaurant->externalDeliveryEnabled(),
            'external_dispatches_today' => $externalDispatchesToday,
            'failed_dispatches_today' => $failedDispatchesToday,
            'active_coupons_count' => $activeCouponsCount,
            'pending_action' => $pendingAction,
        ];
    }

    /**
     * Super Admin (Sham) tools: Platform-wide subscription metrics, delivery providers, and feature catalog.
     *
     * @return array<string, mixed>
     */
    protected function executeSuperAdminTools(string $prompt, string $intent = 'platform_subscription_analytics'): array
    {
        return [
            'total_restaurants' => Restaurant::query()->count(),
            'active_restaurants' => Restaurant::query()->where('is_active', true)->count(),
            'pro_restaurants' => Restaurant::query()->where('plan', 'pro')->count(),
            'starter_restaurants' => Restaurant::query()->where('plan', 'starter')->count(),
            'active_subscription_plans' => SubscriptionPlan::query()->where('is_active', true)->count(),
            'active_delivery_providers' => DeliveryProvider::query()->where('is_active', true)->count(),
            'feature_catalog_count' => count(PlatformFeature::defaultCatalog()),
        ];
    }

    /**
     * Build domain-trained Customer reply tailored to the classified intent.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $entities
     */
    protected function buildCustomerReply(
        ?Restaurant $restaurant,
        string $prompt,
        string $intent,
        array $data,
        array $entities = [],
    ): string {
        if ($restaurant === null) {
            return 'مرحباً بك! يرجى فتح صفحة المطعم المطلوب لمساعدتك في اختيار الوجبات، حساب رسوم التوصيل حسب موقعك، أو تتبع طلبك.';
        }

        $currency = $restaurant->currency_symbol ?: 'ل.س';
        $parts = ["مرحباً بك في {$restaurant->name}!"];

        if (! $restaurant->canAcceptOrders()) {
            $parts[] = 'تنبيه: المطعم حالياً لا يستقبل طلبات فورية ('
                .($restaurant->is_accepting_orders ? 'خارج أوقات العمل الرسمية' : 'متوقف مؤقتاً بسبب ضغط الطلبات').').';
        }

        // 1. Order Tracking Intent
        if ($intent === 'order_tracking' || ! empty($data['tracked_order'])) {
            if (! empty($data['tracked_order'])) {
                $order = $data['tracked_order'];
                $parts[] = sprintf(
                    'حالة طلبك رقم #%s حالياً: (%s - %s)، الإجمالي: %s %s.',
                    $order['tracking_code'] ?: $order['id'],
                    $order['status_label'] ?? $order['status'],
                    $order['status'],
                    number_format((float) $order['total_price'], 2),
                    $currency
                );
                if (! empty($order['delivery_provider_name'])) {
                    $parts[] = "يتم التوصيل عبر: {$order['delivery_provider_name']}.";
                }
                if (! empty($order['estimated_duration_minutes'])) {
                    $parts[] = "الوقت التقديري للوصول: {$order['estimated_duration_minutes']} دقيقة.";
                }

                return implode(' ', $parts);
            }

            if ($intent === 'order_tracking') {
                return "مرحباً بك في {$restaurant->name}! لتتبع طلبك فوراً، يرجى تزويدي برقم الطلب (مثل #105) أو كود التتبع الخاص بك.";
            }
        }

        // 2. Budget & Group Combo Planner Intent
        if (! empty($data['suggested_combo'])) {
            $combo = $data['suggested_combo'];
            $itemNames = collect($combo['items'])
                ->map(fn (array $item): string => "{$item['name']} ({$item['price']} {$currency})")
                ->implode(' + ');

            $parts[] = sprintf(
                'رتبت لك اقتراح وجبة متكاملة لـ %d أشخاص: [%s] بإجمالي %s %s%s.',
                $combo['party_size'],
                $itemNames,
                number_format((float) $combo['total_price'], 2),
                $currency,
                $combo['target_budget'] ? ($combo['within_budget'] ? " (ضمن ميزانيتك المحددة {$combo['target_budget']} {$currency})" : '') : ''
            );
        }

        // 3. Offers & Coupons Intent
        if ($intent === 'offers_and_coupons') {
            if (! empty($data['active_coupons']) || ! empty($data['active_offers'])) {
                if (! empty($data['active_coupons'])) {
                    $couponsText = collect($data['active_coupons'])
                        ->map(fn (array $c): string => "الكود [{$c['code']}] ({$c['label']}، الحد الأدنى: {$c['min_order_amount']} {$currency})")
                        ->implode('، ');
                    $parts[] = "أكواد الخصم الفعالة الآن: {$couponsText}.";
                }
                if (! empty($data['active_offers'])) {
                    $offersText = collect($data['active_offers'])
                        ->map(fn (array $o): string => "{$o['title']} ({$o['label']})")
                        ->implode('، ');
                    $parts[] = "العروض الترويجية المتاحة: {$offersText}.";
                }

                return implode(' ', $parts);
            }

            $parts[] = 'لا توجد كوبونات خصم إضافية فعالة في هذه اللحظة، لكن يمكنك الاستفادة من أسعار الوجبات المميزة في القائمة.';
        }

        // 4. Table Reservation Help
        if ($intent === 'table_reservation_help') {
            $tables = $data['tables_summary'] ?? [];
            if (! empty($tables['dine_in_enabled'])) {
                $parts[] = sprintf(
                    'يتوفر لدينا %d طاولة مفعّلة للجلوس داخل المطعم (سعة حتى %d مقاعد للطاولة الواحدة). يمكنك الطلب مباشرة عبر مسح رمز QR على الطاولة أو اختيار الطلب الداخلي عند إتمام الطلب.',
                    $tables['active_tables_count'] ?? 0,
                    max(2, (int) ($tables['max_seats'] ?? 4))
                );
            } else {
                $parts[] = 'خدمة الجلوس الداخلي غير مفعّلة حالياً، ويمكنك الطلب للتوصيل أو الاستلام المباشر من الفرع.';
            }

            return implode(' ', $parts);
        }

        // 5. Restaurant Hours & Contact Info
        if ($intent === 'restaurant_info_hours') {
            $hours = ($restaurant->opening_time && $restaurant->closing_time)
                ? "من {$restaurant->opening_time} حتى {$restaurant->closing_time}"
                : 'طوال اليوم';
            $parts[] = "أوقات العمل: {$hours}.";
            if ($restaurant->address) {
                $parts[] = "العنوان: {$restaurant->address}.";
            }
            if ($restaurant->phone) {
                $parts[] = "للتواصل المباشر: {$restaurant->phone}.";
            }

            return implode(' ', $parts);
        }

        // 6. Menu Recommendations
        if (! empty($data['recommended_products']) && empty($data['suggested_combo'])) {
            $topNames = collect($data['recommended_products'])
                ->take(3)
                ->map(fn (array $p): string => "{$p['name']} ({$p['price']} {$currency})")
                ->implode('، ');
            $parts[] = "أبرز الأصناف المتاحة الآن: {$topNames}.";
        }

        // 7. Delivery Quotes (Internal or Eligible External Providers)
        if (! empty($data['internal_delivery_quote'])) {
            $iq = $data['internal_delivery_quote'];
            $parts[] = "التوصيل متاح عبر {$iq['provider_name']} لموقعك بتكلفة {$iq['fee']} {$currency} (المسافة: {$iq['distance_km']} كم، الوقت التقديري: {$iq['duration_minutes']} دقيقة).";
        } elseif (! empty($data['eligible_delivery_providers'])) {
            $best = $data['eligible_delivery_providers'][0];
            $parts[] = "أفضل خيار توصيل متاح لموقعك: {$best['provider_name']} بتكلفة {$best['fee']} {$currency} ووقت تقديري {$best['duration_minutes']} دقيقة.";
        } elseif ($intent === 'delivery_quote_and_zones') {
            $parts[] = 'لحساب رسوم التوصيل الدقيقة ومعرفة شركات التوصيل المؤهلة لمنطقتك، اضغط على زر «مشاركة موقعي» أسفل المحادثة.';
        }

        return implode(' ', $parts);
    }

    /**
     * Build domain-trained Restaurant Admin reply tailored to the classified operational/financial intent.
     *
     * @param  array<string, mixed>  $data
     */
    protected function buildRestaurantAdminReply(
        Restaurant $restaurant,
        string $prompt,
        string $intent,
        array $data,
    ): string {
        $currency = $restaurant->currency_symbol ?: 'ل.س';

        if ($intent === 'operational_command' && ! empty($data['pending_action'])) {
            $action = $data['pending_action'];

            return sprintf(
                'تنبيه تشغيلي لمطعم (%s): لقد طلبت «%s». يرجى الضغط على زر التأكيد أدناه لتنفيذ التغيير فوراً في الخادم.',
                $restaurant->name,
                $action['label']
            );
        }

        if ($intent === 'financial_profit_analysis') {
            $topExpenseText = ! empty($data['top_expense_categories'])
                ? collect($data['top_expense_categories'])
                    ->map(fn (array $e): string => "{$e['category']}: {$e['total']} {$currency}")
                    ->implode('، ')
                : 'لا توجد مصاريف مسجلة هذا الشهر';

            return sprintf(
                'التحليل المالي لمطعم (%s): مبيعات اليوم %0.2f %s (صافي ربح اليوم بعد المصاريف: %0.2f %s، متوسط قيمة الطلب: %0.2f %s). خلال الشهر الحالي: إجمالي الإيرادات %0.2f %s مقابل مصاريف تشغيلية %0.2f %s، ليبلغ صافي الربح الشهري %0.2f %s. أبرز بنود المصاريف: (%s).',
                $restaurant->name,
                $data['today_revenue'] ?? 0.0,
                $currency,
                $data['today_net_profit'] ?? 0.0,
                $currency,
                $data['today_average_order'] ?? 0.0,
                $currency,
                $data['month_revenue'] ?? 0.0,
                $currency,
                $data['month_expenses'] ?? 0.0,
                $currency,
                $data['month_net_profit'] ?? 0.0,
                $currency,
                $topExpenseText
            );
        }

        if ($intent === 'menu_engineering_matrix') {
            $topItemsText = ! empty($data['top_selling_products'])
                ? collect($data['top_selling_products'])
                    ->take(3)
                    ->map(fn (array $p): string => "{$p['name']} ({$p['qty']} طلب - {$p['revenue']} {$currency})")
                    ->implode('، ')
                : 'لا توجد مبيعات مسجلة للأصناف بعد';

            return sprintf(
                'تقرير هندسة المنيو لمطعم (%s): الأصناف الأكثر مبيعاً وإيراداً هي: [%s]. يوجد حالياً %d صنف غير متوفر في القائمة. توصية: اربط الصنف الأكثر مبيعاً مع مشروب أو مقبلات في عرض كومبو لرفع متوسط سلة الشراء.',
                $restaurant->name,
                $topItemsText,
                $data['unavailable_products_count'] ?? 0
            );
        }

        if ($intent === 'kitchen_kds_diagnostics') {
            return sprintf(
                'تشخيص شاشة المطبخ (KDS) في (%s): يوجد حالياً %d طلب قيد التحضير/الانتظار، و%d طلب جاهز للتسليم، و%d طلب متأخر لأكثر من 20 دقيقة. الطلبات المحلية بانتظار المزامنة: %d. %s',
                $restaurant->name,
                $data['pending_kitchen_orders'] ?? 0,
                $data['ready_kitchen_orders'] ?? 0,
                $data['delayed_kitchen_orders'] ?? 0,
                $data['pending_local_outbox'] ?? 0,
                ($data['delayed_kitchen_orders'] ?? 0) > 2
                    ? 'توصية عاجلة: يُنصح بتفعيل وضع الزحمة مؤقتاً حتى تفريغ الطلبات المتأخرة.'
                    : 'سير العمل في المطبخ مستقر حالياً.'
            );
        }

        if ($intent === 'delivery_fleet_operations') {
            return sprintf(
                'تقرير عمليات التوصيل في (%s): حالة المناديب الداخليين: %s. التوصيل الخارجي: %s (إحالات اليوم: %d، منها %d متعثرة).',
                $restaurant->name,
                ! empty($data['internal_drivers_available']) ? 'متوفرون وجاهزون' : 'غير متوفرين حالياً',
                ! empty($data['external_delivery_enabled']) ? 'مفعّل' : 'غير مفعّل',
                $data['external_dispatches_today'] ?? 0,
                $data['failed_dispatches_today'] ?? 0
            );
        }

        if ($intent === 'marketing_coupon_advisor') {
            return sprintf(
                'مستشار التسويق والعروض لمطعم (%s): لديك حالياً %d كوبون خصم نشط، ومتوسط قيمة الطلب اليوم هو %0.2f %s. توصية: أنشئ كوبون خصم بنسبة 15%% للطلبات التي تتجاوز %0.2f %s لتحفيز العملاء على زيادة حجم السلة.',
                $restaurant->name,
                $data['active_coupons_count'] ?? 0,
                $data['today_average_order'] ?? 0.0,
                $currency,
                max(25.0, round(($data['today_average_order'] ?: 20.0) * 1.35, 0)),
                $currency
            );
        }

        return sprintf(
            'ملخص عمليات مطعم (%s) اليوم: عدد الطلبات %d بإجمالي مبيعات %0.2f %s (صافي ربح اليوم: %0.2f %s)، ويوجد حالياً %d طلب قيد التحضير في المطبخ (KDS). حالة السائقين الداخليين: %s.',
            $restaurant->name,
            $data['today_orders_count'] ?? 0,
            $data['today_revenue'] ?? 0.0,
            $currency,
            $data['today_net_profit'] ?? 0.0,
            $currency,
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
     * Build structured UI action cards for Customer widget (add-to-cart items, coupons, delivery options).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function buildCustomerActionCards(?Restaurant $restaurant, string $intent, array $data): array
    {
        if ($restaurant === null) {
            return [];
        }

        $cards = [];
        $currency = $restaurant->currency_symbol ?: 'ل.س';

        if (! empty($data['suggested_combo']['items'])) {
            foreach ($data['suggested_combo']['items'] as $item) {
                $cards[] = [
                    'type' => 'product',
                    'product_id' => $item['id'],
                    'title' => $item['name'],
                    'subtitle' => 'ضمن الكومبو المقترح لميزانيتك',
                    'metric' => number_format((float) $item['price'], 2)." {$currency}",
                    'cta_label' => 'أضف للسلة',
                ];
            }
        } elseif (! empty($data['recommended_products'])) {
            foreach ( array_slice($data['recommended_products'], 0, 4) as $product) {
                $cards[] = [
                    'type' => 'product',
                    'product_id' => $product['id'],
                    'title' => $product['name'],
                    'subtitle' => $product['category'] ?: ($product['description'] ?? 'وجبة طازجة'),
                    'metric' => number_format((float) $product['price'], 2)." {$currency}",
                    'cta_label' => 'أضف للسلة',
                ];
            }
        }

        if (! empty($data['active_coupons'])) {
            foreach (array_slice($data['active_coupons'], 0, 2) as $coupon) {
                $cards[] = [
                    'type' => 'coupon',
                    'code' => $coupon['code'],
                    'title' => "كود خصم: {$coupon['code']}",
                    'subtitle' => "الحد الأدنى للطلب: {$coupon['min_order_amount']} {$currency}",
                    'metric' => $coupon['label'],
                    'cta_label' => 'نسخ الكود',
                ];
            }
        }

        return $cards;
    }

    /**
     * Build structured UI diagnostic cards for Restaurant Admin Copilot.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function buildRestaurantAdminActionCards(Restaurant $restaurant, string $intent, array $data): array
    {
        $currency = $restaurant->currency_symbol ?: 'ل.س';

        return [
            [
                'type' => 'kpi',
                'title' => 'مبيعات اليوم المؤكدة',
                'metric' => number_format((float) ($data['today_revenue'] ?? 0), 2)." {$currency}",
                'subtitle' => sprintf('عدد الطلبات: %d | متوسط الطلب: %0.2f %s', $data['today_orders_count'] ?? 0, $data['today_average_order'] ?? 0, $currency),
            ],
            [
                'type' => 'kpi',
                'title' => 'صافي ربح الشهر',
                'metric' => number_format((float) ($data['month_net_profit'] ?? 0), 2)." {$currency}",
                'subtitle' => sprintf('الإيرادات: %0.2f | المصاريف: %0.2f %s', $data['month_revenue'] ?? 0, $data['month_expenses'] ?? 0, $currency),
            ],
            [
                'type' => 'kpi',
                'title' => 'حالة المطبخ (KDS)',
                'metric' => ($data['pending_kitchen_orders'] ?? 0).' قيد التحضير',
                'subtitle' => sprintf('جاهز للتسليم: %d | متأخر (+20 د): %d', $data['ready_kitchen_orders'] ?? 0, $data['delayed_kitchen_orders'] ?? 0),
            ],
        ];
    }

    protected function translateOrderStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'قيد الانتظار والمراجعة',
            'confirmed' => 'تم تأكيد الطلب',
            'preparing' => 'قيد التحضير في المطبخ',
            'ready' => 'جاهز للتسليم / الاستلام',
            'out_for_delivery' => 'مع المندوب في الطريق إليك',
            'delivered' => 'تم التوصيل بنجاح',
            'cancelled' => 'ملغى',
            default => $status,
        };
    }

    /**
     * Optional call to external LLM API if configured in .env (`GEMINI_API_KEY` or `AI_AGENT_API_KEY`).
     * Uses the domain-trained system prompt with live database context and strict tenant isolation.
     *
     * @param  array<string, mixed>  $structuredData
     * @param  array<int, array<string, mixed>>  $history
     */
    protected function attemptConfiguredLlmCall(
        string $roleScope,
        ?Restaurant $restaurant,
        string $intent,
        string $cleanPrompt,
        array $structuredData,
        string $fallbackReply,
        array $history = [],
    ): ?string {
        $systemPrompt = $this->knowledgeTrainer->buildTrainedSystemPrompt(
            $roleScope,
            $restaurant,
            $intent,
            $structuredData,
        );

        // 1. Check Google Gemini API configuration first if GEMINI_API_KEY is present
        $geminiKey = (string) config('services.gemini.key', env('GEMINI_API_KEY', ''));
        if ($geminiKey !== '') {
            try {
                $geminiModel = (string) config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));
                $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}";

                $contents = [];
                foreach (array_slice($history, -4) as $turn) {
                    if (! empty($turn['text']) && is_string($turn['text'])) {
                        $contents[] = [
                            'role' => ($turn['role'] ?? 'user') === 'assistant' ? 'model' : 'user',
                            'parts' => [['text' => $this->sanitizePrompt($turn['text'])]],
                        ];
                    }
                }
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $cleanPrompt]],
                ];

                $response = Http::timeout(8)->post($geminiUrl, [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.25,
                        'maxOutputTokens' => 500,
                    ],
                ]);

                if ($response->successful()) {
                    $candidateText = $response->json('candidates.0.content.parts.0.text');
                    if (is_string($candidateText) && trim($candidateText) !== '') {
                        return trim($candidateText);
                    }
                }
            } catch (Throwable) {
                return $fallbackReply;
            }
        }

        // 2. Check custom/OpenAI-compatible AI_AGENT_ENDPOINT configuration
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
                    'intent' => $intent,
                    'system_prompt' => $systemPrompt,
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
