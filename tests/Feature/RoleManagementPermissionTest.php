<?php

namespace Tests\Feature;

use App\Models\CarInspectionRequest;
use App\Models\CarModel;
use App\Models\City;
use App\Models\User;
use App\Services\StaffRoleService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleManagementPermissionTest extends TestCase
{
    use DatabaseTransactions;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'qa-role-'.Str::uuid(),
            'guard_name' => 'web',
            'custom_role' => 1,
        ]);
        $this->staff = User::factory()->create();
        $this->staff->syncRoles([$role]);
        $this->actingAs($this->staff);
    }

    public function test_customer_list_permission_is_read_only_in_the_page_and_table_payload(): void
    {
        $this->grant('customer-list');

        $customer = User::factory()->create(['email' => 'readonly-customer@example.test']);
        $customer->syncRoles([Role::findOrCreate('User', 'web')]);

        $this->get(route('customer.index'))
            ->assertOk()
            ->assertDontSee('<div id="assignPackageModal"', false)
            ->assertDontSee('autoApproveItemSwitchFormatter', false)
            ->assertSee('userStatusBadgeFormatter', false);

        $response = $this->getJson(route('customer.show', ['customer' => 1, 'search' => $customer->email]))
            ->assertOk();

        self::assertSame('', $response->json('rows.0.operate'));
    }

    public function test_service_request_status_actions_require_update_permission(): void
    {
        $this->grant('car-inspection-request-list');

        $requestRecord = CarInspectionRequest::query()->create([
            'full_name' => 'Permission Test Customer',
            'phone_number' => '+923001111111',
            'city_id' => City::query()->value('id'),
            'car_model_id' => CarModel::query()->value('id'),
            'model_year' => 2022,
            'car_variant' => 'GLX',
            'car_condition' => 'used',
            'visit_area' => 'Gulberg',
            'visit_date' => now()->addDay()->format('Y-m-d'),
            'visit_start_time' => '10:00:00',
            'visit_end_time' => '11:00:00',
            'status' => CarInspectionRequest::STATUS_PENDING,
        ]);

        $url = route('service-requests.table', [
            'section' => 'car-inspection',
            'search' => $requestRecord->id,
        ]);

        $readOnlyResponse = $this->getJson($url)->assertOk();
        self::assertStringNotContainsString('update-request-status', $readOnlyResponse->json('rows.0.operate'));

        $this->grant('car-inspection-request-update');

        $updateResponse = $this->getJson($url)->assertOk();
        self::assertStringContainsString('update-request-status', $updateResponse->json('rows.0.operate'));
    }

    public function test_advertisement_and_news_list_roles_open_their_modules(): void
    {
        $this->grant('advertisement-list', 'news-list');

        $this->get(route('advertisement.index'))
            ->assertOk()
            ->assertSee('Advertisement');

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('News');
        $this->getJson(route('news.show', ['news' => 0]))->assertOk();
    }

    public function test_staff_role_reassignment_removes_every_previous_role(): void
    {
        $staleRole = Role::create([
            'name' => 'stale-role-'.Str::uuid(),
            'guard_name' => 'web',
            'custom_role' => 1,
        ]);
        $replacementRole = Role::create([
            'name' => 'replacement-role-'.Str::uuid(),
            'guard_name' => 'web',
            'custom_role' => 1,
        ]);
        $this->staff->assignRole($staleRole);

        app(StaffRoleService::class)->syncCustomRole($this->staff, $replacementRole->id);

        self::assertSame([$replacementRole->name], $this->staff->fresh()->roles->pluck('name')->all());
    }

    private function grant(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->staff->givePermissionTo($permissions);
    }
}
