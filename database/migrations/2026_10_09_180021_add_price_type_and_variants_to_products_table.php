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
        Schema::table('products', function (Blueprint $table) {
            // نوع السعر: fixed (ثابت)، open_price (مبلغ حر)، variant (أوزان/أحجام)
            $table->string('price_type')->default('fixed')->after('price');

            // الحد الأدنى والأقصى للمبلغ الحر (في حال كان open_price)
            $table->decimal('min_price', 10, 2)->nullable()->after('price_type');
            $table->decimal('max_price', 10, 2)->nullable()->after('min_price');

            // مصفوفة خيارات الأوزان/الأحجام المحددة بصيغة JSON (في حال كان variant)
            // مثال: [{"name": "ربع كيلو", "price": 5000}, {"name": "نصف كيلو", "price": 10000}]
            $table->json('variants')->nullable()->after('max_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_type', 'min_price', 'max_price', 'variants']);
        });
    }
};
