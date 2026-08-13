<?php

namespace Tests\Unit;

use App\Http\Controllers\ServiceRequestAdminController;
use Database\Seeders\SystemUpgradeSeeder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RolePermissionContractTest extends TestCase
{
    public function test_service_request_permissions_separate_read_and_update_access(): void
    {
        $sections = (new ReflectionClass(ServiceRequestAdminController::class))
            ->getConstant('SECTIONS');

        self::assertSame('car-inspection-request-list', $sections['car-inspection']['list_permission']);
        self::assertSame('car-inspection-request-update', $sections['car-inspection']['update_permission']);
        self::assertSame('sell-for-me-request-list', $sections['sell-for-me']['list_permission']);
        self::assertSame('sell-for-me-request-update', $sections['sell-for-me']['update_permission']);
    }

    public function test_upgrade_seeder_includes_service_request_update_permissions(): void
    {
        $permissions = SystemUpgradeSeeder::generatePermissionList([
            'car-inspection-request' => ['only' => ['list', 'update']],
            'sell-for-me-request' => ['only' => ['list', 'update']],
        ]);

        self::assertSame([
            'car-inspection-request-list',
            'car-inspection-request-update',
            'sell-for-me-request-list',
            'sell-for-me-request-update',
        ], $permissions);
    }

    public function test_sidebar_uses_current_advertisement_permission_names(): void
    {
        $sidebar = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/sidebar.blade.php');

        self::assertStringContainsString("@canany(['advertisement-list', 'advertisement-update', 'advertisement-delete'", $sidebar);
        self::assertStringNotContainsString("@canany(['item-list', 'item-create', 'item-update', 'item-delete'", $sidebar);
    }

    public function test_list_only_customer_view_does_not_render_mutation_controls(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/customer/index.blade.php');

        self::assertStringContainsString("@can('customer-update')", $view);
        self::assertStringContainsString('data-formatter="userStatusBadgeFormatter"', $view);
        self::assertStringContainsString('<div id="assignPackageModal"', $view);
    }
}
