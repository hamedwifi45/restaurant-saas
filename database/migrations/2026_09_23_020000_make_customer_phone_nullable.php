<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')
            || ! Schema::hasColumn('orders', 'customer_phone')
            || DB::table('orders')->whereNull('customer_phone')->exists()) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_phone')->nullable(false)->change();
        });
    }
};
