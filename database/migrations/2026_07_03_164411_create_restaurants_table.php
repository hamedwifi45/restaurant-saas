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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            
            // Basic Info
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subdomain')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            
            // Contact & Location
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable()->default(0);
            $table->integer('estimated_delivery_time')->nullable();
            
            // Theme & Design
            $table->foreignId('theme_id')->constrained('themes')->onDelete('restrict');
            $table->string('primary_color')->default('#FF6B35');
            $table->string('secondary_color')->default('#FFFFFF');
            $table->string('background_color')->default('#1A1A1A');
            
            // Payment (QR Code)
            $table->string('qr_code_image')->nullable();
            $table->text('bank_details')->nullable();
            
            // Pricing Model
            $table->enum('pricing_type', ['commission', 'subscription'])->default('commission');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('subscription_fee', 10, 2)->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Performance Indexes
            $table->index('subdomain');
            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
