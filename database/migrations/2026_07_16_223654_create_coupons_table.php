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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            
            // معلومات الكوبون
            $table->string('code')->unique(); // كود الخصم (مثل: SAVE20)
            $table->string('name')->nullable(); // اسم وصفي داخلي
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            
            // نوع الخصم
            $table->enum('type', [
                'percentage',    // نسبة مئوية
                'fixed_amount',  // مبلغ ثابت
            ])->default('percentage');
            
            $table->decimal('value', 10, 2)->default(0);
            
            // الشروط
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->integer('max_uses')->nullable(); // حد أقصى للاستخدامات
            $table->integer('used_count')->default(0);
            $table->integer('max_uses_per_user')->nullable(); // حد لكل زبون (بناءً على الهاتف)
            
            // فترة الصلاحية
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // نطاق التطبيق
            $table->boolean('apply_to_all')->default(true);
            $table->json('product_ids')->nullable(); // منتجات محددة
            
            $table->timestamps();
            
            // فهارس
            $table->index('restaurant_id');
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
