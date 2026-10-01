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
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['restaurant_id', 'status', 'created_at']);
            $table->index(['restaurant_id', 'payment_method']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['restaurant_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'is_available']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'payment_method']);
            $table->dropIndex(['restaurant_id', 'status', 'created_at']);
        });
    }
};
