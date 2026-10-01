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
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('final_amount', 10, 2)->default(0)->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee', 'final_amount']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
        });
    }
};
