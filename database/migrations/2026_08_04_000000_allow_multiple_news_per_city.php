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
        // Keep a non-unique index for the city foreign key before removing the
        // unique index that currently supports it.
        Schema::table('news', static function (Blueprint $table) {
            $table->index('city_id');
        });

        Schema::table('news', static function (Blueprint $table) {
            $table->dropUnique(['city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->unique('city_id');
        });

        Schema::table('news', static function (Blueprint $table) {
            $table->dropIndex(['city_id']);
        });
    }
};
