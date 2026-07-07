<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('author')->nullable()->after('description');
            $table->string('version')->default('1.0')->after('author');
            $table->json('sections')->nullable()->after('version');
            $table->json('settings')->nullable()->after('sections');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->json('theme_settings')->nullable()->after('theme_id');
            $table->json('custom_sections')->nullable()->after('theme_settings');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['author', 'version', 'sections', 'settings']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['theme_settings', 'custom_sections']);
        });
    }
};
