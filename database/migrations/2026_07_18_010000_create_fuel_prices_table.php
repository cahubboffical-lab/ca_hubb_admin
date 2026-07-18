<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fuel_prices')) {
            Schema::create('fuel_prices', static function (Blueprint $table) {
                $table->id();
                $table->decimal('petrol_super', 10, 2);
                $table->decimal('high_octane', 10, 2);
                $table->decimal('high_speed_diesel', 10, 2);
                $table->decimal('lpg', 10, 2);
                $table->decimal('kerosene_oil', 10, 2);
                $table->timestamps();

                $table->index('created_at', 'fuel_prices_created_at_index');
            });
        }

        if (Schema::hasTable('permissions')) {
            $now = now();
            DB::table('permissions')->upsert([
                $this->permission('fuel-price-list', $now),
                $this->permission('fuel-price-create', $now),
                $this->permission('fuel-price-update', $now),
                $this->permission('fuel-price-delete', $now),
            ], ['name', 'guard_name'], ['updated_at']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', [
                'fuel-price-list',
                'fuel-price-create',
                'fuel-price-update',
                'fuel-price-delete',
            ])->delete();
        }

        Schema::dropIfExists('fuel_prices');
    }

    private function permission(string $name, $now): array
    {
        return [
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
};
