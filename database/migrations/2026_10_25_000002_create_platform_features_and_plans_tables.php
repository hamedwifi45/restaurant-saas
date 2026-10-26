<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_features', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('category', 64)->default('operations');
            $table->text('description_ar')->nullable();
            $table->decimal('approximate_monthly_price', 10, 2)->default(0.00);
            $table->string('currency_code', 12)->default('USD');
            $table->boolean('is_core')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0.00);
            $table->decimal('annual_price', 10, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 2)->default(0.00);
            $table->json('feature_keys')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('restaurant_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_feature_id')->constrained('platform_features')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->decimal('custom_monthly_price', 10, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'platform_feature_id'], 'rest_feature_unique');
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 120);
            $table->string('auditable_type', 120)->nullable();
            $table->string('auditable_id', 64)->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('restaurant_features');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('platform_features');
    }
};
