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
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedSmallInteger('dine_in_duration_minutes')->nullable()->after('dine_in_arrival_at');
            $table->string('reservation_status', 20)->nullable()->after('dine_in_duration_minutes')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['reservation_status']);
            $table->dropColumn(['dine_in_duration_minutes', 'reservation_status']);
        });
    }
};
