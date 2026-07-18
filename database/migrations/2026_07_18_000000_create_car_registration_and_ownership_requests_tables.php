<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createRequestTable('car_registration_requests', 'car_registration');
        $this->createRequestTable('car_ownership_requests', 'car_ownership');

        if (Schema::hasTable('permissions')) {
            $now = now();
            DB::table('permissions')->upsert([
                $this->permission('car-registration-request-list', $now),
                $this->permission('car-registration-request-update', $now),
                $this->permission('car-ownership-request-list', $now),
                $this->permission('car-ownership-request-update', $now),
            ], ['name', 'guard_name'], ['updated_at']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', [
                'car-registration-request-list',
                'car-registration-request-update',
                'car-ownership-request-list',
                'car-ownership-request-update',
            ])->delete();
        }

        Schema::dropIfExists('car_ownership_requests');
        Schema::dropIfExists('car_registration_requests');
    }

    private function createRequestTable(string $tableName, string $indexPrefix): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, static function (Blueprint $table) use ($indexPrefix) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name', 150);
            $table->string('phone_number', 30);
            $table->string('phone_number_normalized', 30);
            $table->boolean('is_filer');
            $table->foreignId('car_model_id')->constrained('car_models')->restrictOnDelete();
            $table->unsignedSmallInteger('model_year');
            $table->string('car_variant', 150);
            $table->string('registration_place', 30);
            $table->string('status', 30)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('phone_number', $indexPrefix.'_phone_index');
            $table->index('phone_number_normalized', $indexPrefix.'_normalized_phone_index');
            $table->index('status', $indexPrefix.'_status_index');
            $table->index('registration_place', $indexPrefix.'_place_index');
            $table->index(['status', 'created_at'], $indexPrefix.'_queue_index');
            $table->index(
                ['phone_number_normalized', 'car_model_id', 'model_year', 'registration_place', 'status'],
                $indexPrefix.'_duplicate_index'
            );
        });
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
