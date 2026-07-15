<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('car_models', 'price')) {
            Schema::table('car_models', static function (Blueprint $table) {
                $table->integer('price')->nullable()->after('brand_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('car_models', 'price')) {
            Schema::table('car_models', static function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
