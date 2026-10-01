<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('currency_symbol', 12)->default('ل.س')->after('city');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('currency_symbol');
            $table->string('whatsapp_number')->nullable()->after('tax_percentage');
            $table->string('instagram_url')->nullable()->after('whatsapp_number');
            $table->string('facebook_url')->nullable()->after('instagram_url');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'currency_symbol',
                'tax_percentage',
                'whatsapp_number',
                'instagram_url',
                'facebook_url',
            ]);
        });
    }
};
