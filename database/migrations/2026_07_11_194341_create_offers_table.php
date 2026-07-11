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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            
            // معلومات العرض
            $table->string('title'); // عنوان العرض
            $table->text('description')->nullable(); // وصف العرض
            $table->string('image')->nullable(); // صورة العرض
            $table->boolean('is_active')->default(true); // هل العرض نشط
            
            // نوع العرض
            $table->enum('type', [
                'percentage',      // خصم نسبة مئوية (مثل 20%)
                'fixed_amount',    // خصم مبلغ ثابت (مثل 10 ريال)
                'free_product',    // منتج مجاني (اطلب 2 واحصل على 1)
                'free_shipping'    // شحن مجاني
            ])->default('percentage');
            
            // قيمة الخصم
            $table->decimal('value', 10, 2)->default(0); // قيمة الخصم (نسبة أو مبلغ)
            
            // شروط العرض
            $table->decimal('min_order_amount', 10, 2)->default(0); // حد أدنى للطلب
            $table->integer('max_uses')->nullable(); // حد أقصى للاستخدامات (null = غير محدود)
            $table->integer('used_count')->default(0); // عدد مرات الاستخدام
            
            // فترة العرض
            $table->timestamp('starts_at')->nullable(); // تاريخ البداية
            $table->timestamp('ends_at')->nullable(); // تاريخ النهاية
            
            // منتجات معينة (اختياري)
            $table->boolean('apply_to_all')->default(true); // هل ينطبق على كل المنتجات؟
            $table->json('product_ids')->nullable(); // IDs المنتجات المحددة (إذا لم يكن apply_to_all)
            
            $table->timestamps();
            
            // فهارس للأداء
            $table->index('restaurant_id');
            $table->index('is_active');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
