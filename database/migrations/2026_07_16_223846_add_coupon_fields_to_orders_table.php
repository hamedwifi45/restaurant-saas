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
             // بيانات الكوبون (منفصلة عن العرض)
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable(); // حفظ الكود (في حال حذف الكوبون)
            $table->decimal('coupon_discount', 10, 2)->default(0);
            
            // إعادة تسمية discount_amount إلى offer_discount للوضوح
            // ملاحظة: إذا كان لديك بيانات موجودة، سنحتاج rename
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'coupon_code', 'coupon_discount']);
        });
    }
};
