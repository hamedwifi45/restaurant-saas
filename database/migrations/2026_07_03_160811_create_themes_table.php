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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الثيم (مثال: "برجر كلاسيك")
            $table->string('slug')->unique(); // رابط فريد (مثال: "burger-classic")
            $table->string('author')->nullable(); // اسم المبرمج/المصمم
            $table->string('version')->default('1.0.0'); // إصدار الثيم
            $table->text('description')->nullable(); // وصف الثيم
            $table->string('folder_name')->unique(); // اسم المجلد في resources/views/themes/
            $table->string('preview_image')->nullable(); // صورة معاينة الثيم
            $table->json('default_settings')->nullable(); // الإعدادات الافتراضية من theme.json
            $table->json('allowed_variables')->nullable(); // المتغيرات المسموح تعديلها
            $table->boolean('is_active')->default(true); // هل الثيم متاح للاستخدام؟
            $table->boolean('is_default')->default(false); // هل هذا هو الثيم الافتراضي؟
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
