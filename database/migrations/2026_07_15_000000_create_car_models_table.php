<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('car_models')) {
            Schema::create('car_models', static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('brand_name');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['brand_name', 'name']);
                $table->index('name');
            });
        }

        $now = now();
        $models = [
            ['brand_name' => 'Suzuki', 'name' => 'Alto'],
            ['brand_name' => 'Suzuki', 'name' => 'Cultus'],
            ['brand_name' => 'Suzuki', 'name' => 'Wagon R'],
            ['brand_name' => 'Suzuki', 'name' => 'Swift'],
            ['brand_name' => 'Suzuki', 'name' => 'Bolan'],
            ['brand_name' => 'Suzuki', 'name' => 'Mehran'],
            ['brand_name' => 'Toyota', 'name' => 'Corolla'],
            ['brand_name' => 'Toyota', 'name' => 'Yaris'],
            ['brand_name' => 'Toyota', 'name' => 'Fortuner'],
            ['brand_name' => 'Toyota', 'name' => 'Hilux'],
            ['brand_name' => 'Toyota', 'name' => 'Land Cruiser'],
            ['brand_name' => 'Honda', 'name' => 'Civic'],
            ['brand_name' => 'Honda', 'name' => 'City'],
            ['brand_name' => 'Honda', 'name' => 'BR-V'],
            ['brand_name' => 'Honda', 'name' => 'HR-V'],
            ['brand_name' => 'KIA', 'name' => 'Sportage'],
            ['brand_name' => 'KIA', 'name' => 'Picanto'],
            ['brand_name' => 'KIA', 'name' => 'Stonic'],
            ['brand_name' => 'Hyundai', 'name' => 'Tucson'],
            ['brand_name' => 'Hyundai', 'name' => 'Elantra'],
            ['brand_name' => 'Hyundai', 'name' => 'Sonata'],
            ['brand_name' => 'Changan', 'name' => 'Alsvin'],
            ['brand_name' => 'Changan', 'name' => 'Oshan X7'],
            ['brand_name' => 'MG', 'name' => 'HS'],
            ['brand_name' => 'MG', 'name' => 'ZS'],
            ['brand_name' => 'Proton', 'name' => 'Saga'],
            ['brand_name' => 'Haval', 'name' => 'H6'],
            ['brand_name' => 'Daihatsu', 'name' => 'Mira'],
        ];

        DB::table('car_models')->insertOrIgnore(
            array_map(static fn (array $model) => $model + [
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $models)
        );

        if (Schema::hasTable('permissions')) {
            $permissions = array_map(static fn (string $name) => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ], ['car-model-list', 'car-model-create', 'car-model-update', 'car-model-delete']);

            DB::table('permissions')->upsert($permissions, ['name', 'guard_name'], ['updated_at']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', [
                'car-model-list',
                'car-model-create',
                'car-model-update',
                'car-model-delete',
            ])->delete();
        }

        Schema::dropIfExists('car_models');
    }
};
