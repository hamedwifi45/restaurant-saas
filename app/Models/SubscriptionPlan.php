<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name_ar',
        'name_en',
        'description_ar',
        'monthly_price',
        'annual_price',
        'commission_rate',
        'feature_keys',
        'limits',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'feature_keys' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Calculate the sum of approximate individual prices for features included in this plan.
     */
    public function calculateApproximateFeaturesValue(): float
    {
        $keys = $this->feature_keys ?? [];
        if ($keys === []) {
            return 0.0;
        }

        $catalogMap = collect(PlatformFeature::defaultCatalog())->keyBy('key');

        try {
            if (Schema::hasTable('platform_features')) {
                foreach (PlatformFeature::query()->get(['key', 'approximate_monthly_price']) as $dbFeature) {
                    $catalogMap[$dbFeature->key] = [
                        'key' => $dbFeature->key,
                        'approximate_monthly_price' => (float) $dbFeature->approximate_monthly_price,
                    ];
                }
            }
        } catch (\Throwable) {
            // Fallback to defaultCatalog when DB is not migrated yet
        }

        return round(
            collect($keys)->sum(fn (string $key): float => (float) ($catalogMap[$key]['approximate_monthly_price'] ?? 0.0)),
            2
        );
    }

    public function calculatedFeaturesPrice(): float
    {
        return $this->calculateApproximateFeaturesValue();
    }
}
