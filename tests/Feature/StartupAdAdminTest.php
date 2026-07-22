<?php

namespace Tests\Feature;

use App\Models\StartupAd;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StartupAdAdminTest extends TestCase
{
    use DatabaseTransactions;

    public function test_both_sections_have_scoped_crud_and_status_control(): void
    {
        Storage::fake('public');
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $admin = User::query()->firstOrFail();
        $permissions = [
            'startup-ad-list', 'startup-ad-create', 'startup-ad-update', 'startup-ad-delete',
            'inspection-ad-list', 'inspection-ad-create', 'inspection-ad-update', 'inspection-ad-delete',
        ];
        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin->givePermissionTo($permissions);
        $this->actingAs($admin);

        $this->post(route('startup-ads.store', ['section' => 'startup']), [
            'name' => 'General Startup Ad',
            'image' => UploadedFile::fake()->image('general.webp', 600, 900),
            'url' => 'https://example.com/general',
            'is_active' => '1',
        ])->assertRedirect(route('startup-ads.index', ['section' => 'startup']));

        $general = StartupAd::query()->where('name', 'General Startup Ad')->firstOrFail();
        self::assertNull($general->type);
        self::assertTrue($general->is_active);
        self::assertSame($admin->id, $general->created_by);
        Storage::disk('public')->assertExists($general->getRawOriginal('image'));

        $this->put(route('startup-ads.update', ['section' => 'startup', 'startupAdId' => $general->id]), [
            'name' => 'Updated General Ad',
            'url' => '',
            'is_active' => '0',
        ])->assertRedirect(route('startup-ads.index', ['section' => 'startup']));

        $general->refresh();
        self::assertSame('Updated General Ad', $general->name);
        self::assertNull($general->url);
        self::assertNull($general->type);
        self::assertFalse($general->is_active);
        self::assertSame($admin->id, $general->updated_by);

        $this->patchJson(route('startup-ads.toggle', ['section' => 'startup', 'startupAdId' => $general->id]), [
            'is_active' => true,
        ])->assertOk()->assertJsonPath('data.is_active', true);
        self::assertTrue($general->fresh()->is_active);

        $this->post(route('startup-ads.store', ['section' => 'inspection']), [
            'name' => 'Inspection Ad',
            'image' => UploadedFile::fake()->image('inspection.webp', 900, 600),
            'is_active' => '1',
        ])->assertRedirect(route('startup-ads.index', ['section' => 'inspection']));

        $inspection = StartupAd::query()->where('name', 'Inspection Ad')->firstOrFail();
        self::assertSame(StartupAd::TYPE_INSPECTION, $inspection->type);

        $this->get(route('startup-ads.edit', ['section' => 'startup', 'startupAdId' => $inspection->id]))
            ->assertNotFound();

        $generalImage = $general->getRawOriginal('image');
        $this->deleteJson(route('startup-ads.destroy', ['section' => 'startup', 'startupAdId' => $general->id]))
            ->assertOk()
            ->assertJsonPath('error', false);
        $this->assertDatabaseMissing('startup_ads', ['id' => $general->id]);
        Storage::disk('public')->assertMissing($generalImage);
    }
}
