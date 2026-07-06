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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // معلومات الزبون (للضيوف والمسجلين)
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            
            // التوصيل والاستلام
            $table->enum('delivery_type', ['delivery', 'takeaway'])->default('delivery');
            $table->text('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            
            // الدفع والمبالغ
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->string('payment_receipt')->nullable();
            
            // حالة الطلب
            $table->enum('status', [
                'pending', 'confirmed', 'preparing', 
                'ready', 'delivered', 'cancelled'
            ])->default('pending');
            
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // التتبع والتقييم
            $table->string('tracking_code')->unique()->nullable();
            $table->tinyInteger('rating')->nullable()->unsigned(); // 1-5
            $table->text('review')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'status']);
            $table->index('tracking_code');
            $table->index('customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
