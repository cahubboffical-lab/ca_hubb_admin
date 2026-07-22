<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('startup_ads')) {
            Schema::create('startup_ads', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('image');
                $table->text('url')->nullable();
                $table->string('type', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('type');
                $table->index('is_active');
                $table->index(['type', 'is_active'], 'startup_ads_type_active_index');
            });
        }

        if (Schema::hasTable('permissions')) {
            $now = now();
            DB::table('permissions')->upsert([
                $this->permission('startup-ad-list', $now),
                $this->permission('startup-ad-create', $now),
                $this->permission('startup-ad-update', $now),
                $this->permission('startup-ad-delete', $now),
                $this->permission('inspection-ad-list', $now),
                $this->permission('inspection-ad-create', $now),
                $this->permission('inspection-ad-update', $now),
                $this->permission('inspection-ad-delete', $now),
            ], ['name', 'guard_name'], ['updated_at']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', [
                'startup-ad-list',
                'startup-ad-create',
                'startup-ad-update',
                'startup-ad-delete',
                'inspection-ad-list',
                'inspection-ad-create',
                'inspection-ad-update',
                'inspection-ad-delete',
            ])->delete();
        }

        Schema::dropIfExists('startup_ads');
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
