<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('delivery_radius_km', 5, 2)->default(10.00);
            $table->decimal('base_delivery_fee', 8, 2)->default(2.00);
            $table->decimal('base_delivery_km', 5, 2)->default(2.00);
            $table->decimal('per_km_delivery_fee', 8, 2)->default(0.50);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('customer_lat', 10, 7)->nullable();
            $table->decimal('customer_lng', 10, 7)->nullable();
            $table->decimal('distance_km', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['customer_lat', 'customer_lng', 'distance_km']);
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            $table->dropColumn([
                'latitude',
                'longitude',
                'delivery_radius_km',
                'base_delivery_fee',
                'base_delivery_km',
                'per_km_delivery_fee',
            ]);
        });
    }
};
