<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurants', 'currency_symbol')) {
                $table->string('currency_symbol')->default('ل.س');
            }

            if (! Schema::hasColumn('restaurants', 'cover_image')) {
                $table->string('cover_image')->nullable();
            }

            if (! Schema::hasColumn('restaurants', 'whatsapp_number')) {
                $table->string('whatsapp_number')->nullable();
            }

            if (! Schema::hasColumn('restaurants', 'instagram_url')) {
                $table->string('instagram_url')->nullable();
            }

            if (! Schema::hasColumn('restaurants', 'facebook_url')) {
                $table->string('facebook_url')->nullable();
            }
        });
    }

    public function down(): void {}
};
