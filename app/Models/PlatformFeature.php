<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlatformFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name_ar',
        'name_en',
        'category',
        'description_ar',
        'approximate_monthly_price',
        'currency_code',
        'is_core',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'approximate_monthly_price' => 'decimal:2',
        'is_core' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Default platform feature catalog with approximate monthly value.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultCatalog(): array
    {
        return [
            [
                'key' => 'qr_menu',
                'name_ar' => 'المنيو الرقمي التفاعلي (QR Menu)',
                'name_en' => 'Interactive QR Menu',
                'category' => 'core',
                'approximate_monthly_price' => 5.00,
                'is_core' => true,
            ],
            [
                'key' => 'table_qr',
                'name_ar' => 'باركود الطاولات والطلب الداخلي',
                'name_en' => 'Table QR & Dine-In Ordering',
                'category' => 'operations',
                'approximate_monthly_price' => 4.00,
                'is_core' => true,
            ],
            [
                'key' => 'cash_payment',
                'name_ar' => 'الدفع النقدي عند الاستلام',
                'name_en' => 'Cash on Delivery / Pickup',
                'category' => 'payments',
                'approximate_monthly_price' => 0.00,
                'is_core' => true,
            ],
            [
                'key' => 'bank_transfer',
                'name_ar' => 'الحوالات البنكية مع رفع الإشعار',
                'name_en' => 'Bank Transfer with Receipt Upload',
                'category' => 'payments',
                'approximate_monthly_price' => 3.00,
                'is_core' => true,
            ],
            [
                'key' => 'basic_sales_report',
                'name_ar' => 'تقارير المبيعات الأساسية',
                'name_en' => 'Basic Sales Reports',
                'category' => 'analytics',
                'approximate_monthly_price' => 3.00,
                'is_core' => true,
            ],
            [
                'key' => 'advanced_financial_analytics',
                'name_ar' => 'التحليلات المالية المتقدمة وصافي الربح',
                'name_en' => 'Advanced Financial Analytics & Net Profit',
                'category' => 'analytics',
                'approximate_monthly_price' => 9.00,
                'is_core' => false,
            ],
            [
                'key' => 'theme_editor',
                'name_ar' => 'محرر الثيمات والهوية البصرية',
                'name_en' => 'Theme & Brand Editor',
                'category' => 'branding',
                'approximate_monthly_price' => 6.00,
                'is_core' => false,
            ],
            [
                'key' => 'kds',
                'name_ar' => 'شاشة المطبخ اللحظية (KDS) مع الجسر المحلي',
                'name_en' => 'Kitchen Display System (KDS) + Local Bridge',
                'category' => 'operations',
                'approximate_monthly_price' => 10.00,
                'is_core' => false,
            ],
            [
                'key' => 'gps_tracking',
                'name_ar' => 'تتبع المندوب اللحظي عبر الخريطة (GPS)',
                'name_en' => 'Real-time Driver GPS Tracking',
                'category' => 'delivery',
                'approximate_monthly_price' => 8.00,
                'is_core' => false,
            ],
            [
                'key' => 'external_delivery_providers',
                'name_ar' => 'الربط مع شركات التوصيل الخارجية والنطاقات الجغرافية',
                'name_en' => 'External Delivery Providers & Zone Routing',
                'category' => 'delivery',
                'approximate_monthly_price' => 7.00,
                'is_core' => false,
            ],
            [
                'key' => 'pwa',
                'name_ar' => 'تطبيق الجوال التقدمي (PWA)',
                'name_en' => 'Installable Progressive Web App (PWA)',
                'category' => 'branding',
                'approximate_monthly_price' => 5.00,
                'is_core' => false,
            ],
            [
                'key' => 'ai_assistant',
                'name_ar' => 'الوكيل الذكي المدمج (AI Copilot & Concierge)',
                'name_en' => 'Integrated Multi-Role AI Agent',
                'category' => 'ai',
                'approximate_monthly_price' => 12.00,
                'is_core' => false,
            ],
        ];
    }

    /**
     * @return BelongsToMany<Restaurant, $this>
     */
    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_features')
            ->withPivot(['is_enabled', 'custom_monthly_price', 'expires_at', 'notes'])
            ->withTimestamps();
    }
}
