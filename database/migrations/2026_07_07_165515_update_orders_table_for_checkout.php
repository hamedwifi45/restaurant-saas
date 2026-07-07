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
            $table->string('tracking_code')->unique()->nullable(); // رمز التتبع
            $table->decimal('final_amount', 10, 2)->default(0); // المبلغ النهائي
            $table->enum('payment_method', ['cash', 'transfer'])->default('cash');
            $table->string('payment_proof')->nullable(); 
            $table->timestamp('delivered_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
