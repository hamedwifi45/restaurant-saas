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
        if (! Schema::hasColumn('drivers', 'vehicle_model')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->string('vehicle_model')->nullable()->after('vehicle_type');
            });
        }

        if (! Schema::hasColumn('drivers', 'vehicle_type')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->string('vehicle_type')->default('motorcycle');
            });
        } else {
            DB::table('drivers')
                ->whereNull('vehicle_type')
                ->update(['vehicle_type' => 'motorcycle']);

            Schema::table('drivers', function (Blueprint $table) {
                $table->string('vehicle_type')->default('motorcycle')->change();
            });
        }

        if (! Schema::hasColumn('drivers', 'plate_number')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->string('plate_number')->nullable();
            });
        }

        if (! Schema::hasColumn('drivers', 'is_available')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->boolean('is_available')->default(true);
            });
        }

        if (! Schema::hasColumn('drivers', 'rating')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->decimal('rating', 2, 1)->default(5.0);
            });
        } else {
            Schema::table('drivers', function (Blueprint $table) {
                $table->decimal('rating', 2, 1)->default(5.0)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['vehicle_model', 'is_available']);
            $table->string('vehicle_type')->nullable()->default(null)->change();
            $table->decimal('rating', 3, 2)->default(5.00)->change();
        });
    }
};
