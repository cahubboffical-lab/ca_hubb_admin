<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'car-inspection-request-list',
        'car-inspection-request-update',
        'car-inspection-package-list',
        'car-inspection-package-create',
        'car-inspection-package-update',
        'car-inspection-package-delete',
        'sell-for-me-request-list',
        'sell-for-me-request-update',
        'sell-for-me-package-list',
        'sell-for-me-package-create',
        'sell-for-me-package-update',
        'sell-for-me-package-delete',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permissions = array_map(static fn (string $name): array => [
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ], self::PERMISSIONS);

        DB::table('permissions')->upsert(
            $permissions,
            ['name', 'guard_name'],
            ['updated_at']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep permissions and existing role assignments intact on rollback.
    }
};
