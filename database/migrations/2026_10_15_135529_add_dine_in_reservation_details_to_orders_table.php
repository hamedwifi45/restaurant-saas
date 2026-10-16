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
            $table->dateTime('dine_in_arrival_at')->nullable();
            $table->unsignedTinyInteger('dine_in_guest_count')->nullable();
            $table->string('dine_in_seating_preference', 30)->nullable();
            $table->string('dine_in_occasion', 30)->nullable();
            $table->unsignedTinyInteger('dine_in_high_chair_count')->nullable();
            $table->boolean('dine_in_stroller_space')->default(false);
            $table->text('dine_in_accessibility_notes')->nullable();
            $table->text('dine_in_allergy_notes')->nullable();
            $table->string('dine_in_dietary_preference', 30)->nullable();
            $table->boolean('dine_in_confirmation_call')->default(false);
            $table->text('dine_in_celebration_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'dine_in_arrival_at',
                'dine_in_guest_count',
                'dine_in_seating_preference',
                'dine_in_occasion',
                'dine_in_high_chair_count',
                'dine_in_stroller_space',
                'dine_in_accessibility_notes',
                'dine_in_allergy_notes',
                'dine_in_dietary_preference',
                'dine_in_confirmation_call',
                'dine_in_celebration_notes',
            ]);
        });
    }
};
