<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('checkout_token')->nullable();
        });

        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $grammar = DB::connection()->getQueryGrammar();
            $wrappedTable = $grammar->wrapTable('orders');
            $wrappedColumn = $grammar->wrap('checkout_token');
            $wrappedIndex = $grammar->wrap('orders_checkout_token_unique');

            DB::statement(
                "CREATE UNIQUE INDEX {$wrappedIndex} ON {$wrappedTable} ({$wrappedColumn}) WHERE {$wrappedColumn} IS NOT NULL"
            );

            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('checkout_token', 'orders_checkout_token_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlsrv') {
            $grammar = DB::connection()->getQueryGrammar();

            DB::statement(
                'DROP INDEX '.$grammar->wrap('orders_checkout_token_unique').' ON '.$grammar->wrapTable('orders')
            );
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_checkout_token_unique');
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('checkout_token');
        });
    }
};
