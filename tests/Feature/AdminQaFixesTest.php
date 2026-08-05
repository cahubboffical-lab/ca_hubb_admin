<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminQaFixesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subscription_package_delete_is_soft_and_hidden_from_normal_queries(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $admin = User::query()->firstOrFail();
        $permissions = [
            'advertisement-listing-package-list',
            'advertisement-listing-package-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin->givePermissionTo($permissions);

        $package = Package::query()->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson(route('package.destroy', $package->id))
            ->assertOk()
            ->assertJsonPath('error', false);

        $this->assertSoftDeleted('packages', ['id' => $package->id]);
        self::assertNull(Package::find($package->id));
        self::assertNotNull(Package::withTrashed()->find($package->id));

        $response = $this->actingAs($admin)
            ->getJson(route('package.show', 1))
            ->assertOk();

        self::assertNotContains($package->id, collect($response->json('rows'))->pluck('id')->all());
    }

    public function test_current_password_cannot_be_reused_as_the_new_password(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $admin = User::query()->firstOrFail();
        $admin->forceFill(['password' => Hash::make('SamePassword1!')])->save();

        $this->actingAs($admin)
            ->postJson(route('change-password.update'), [
                'old_password' => 'SamePassword1!',
                'new_password' => 'SamePassword1!',
                'confirm_password' => 'SamePassword1!',
            ])
            ->assertOk()
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'New password must be different from the current password.');

        self::assertTrue(Hash::check('SamePassword1!', $admin->fresh()->password));
    }
}
