<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_providers', function (Blueprint $table) {
            $table->string('pricing_model', 32)->default('distance_per_km')->after('assignment_method');
            $table->decimal('base_fee', 10, 2)->default(2.00)->after('pricing_model');
            $table->decimal('base_distance_km', 8, 2)->default(2.00)->after('base_fee');
            $table->decimal('per_km_fee', 10, 2)->default(0.50)->after('base_distance_km');
            $table->decimal('min_distance_km', 8, 2)->default(0.00)->after('per_km_fee');
            $table->decimal('max_distance_km', 8, 2)->default(25.00)->after('min_distance_km');
            $table->decimal('min_order_amount', 10, 2)->default(0.00)->after('max_distance_km');
            $table->unsignedSmallInteger('estimated_pickup_minutes')->default(15)->after('min_order_amount');
            $table->decimal('road_detour_factor', 5, 2)->default(1.28)->after('estimated_pickup_minutes');
            $table->json('allowed_zones')->nullable()->after('road_detour_factor');
            $table->json('blocked_zones')->nullable()->after('allowed_zones');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_provider_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('delivery_providers')
                ->nullOnDelete();
            $table->string('route_source', 32)->nullable()->after('distance_km');
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable()->after('route_source');
            $table->index(['restaurant_id', 'delivery_provider_id', 'status'], 'orders_rest_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_rest_provider_status_idx');
            $table->dropConstrainedForeignId('delivery_provider_id');
            $table->dropColumn(['route_source', 'estimated_duration_minutes']);
        });

        Schema::table('delivery_providers', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_model',
                'base_fee',
                'base_distance_km',
                'per_km_fee',
                'min_distance_km',
                'max_distance_km',
                'min_order_amount',
                'estimated_pickup_minutes',
                'road_detour_factor',
                'allowed_zones',
                'blocked_zones',
            ]);
        });
    }
};
