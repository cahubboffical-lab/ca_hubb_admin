<?php

namespace Tests\Feature;

use App\Models\StartupAd;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StartupAdApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_startup_ads_schema_contains_required_fields(): void
    {
        foreach ([
            'id', 'name', 'image', 'url', 'type', 'is_active',
            'created_at', 'created_by', 'updated_at', 'updated_by',
        ] as $column) {
            self::assertTrue(Schema::hasColumn('startup_ads', $column), 'Missing startup_ads.'.$column);
        }
    }

    public function test_api_returns_one_random_active_general_ad_when_type_is_omitted(): void
    {
        StartupAd::query()->delete();
        $active = StartupAd::query()->create([
            'name' => 'General Active Ad',
            'image' => 'startup-ads/general.webp',
            'url' => 'https://example.com/general',
            'type' => null,
            'is_active' => true,
        ]);
        StartupAd::query()->create([
            'name' => 'General Inactive Ad',
            'image' => 'startup-ads/inactive.webp',
            'type' => null,
            'is_active' => false,
        ]);
        StartupAd::query()->create([
            'name' => 'Inspection Ad',
            'image' => 'startup-ads/inspection.webp',
            'type' => StartupAd::TYPE_INSPECTION,
            'is_active' => true,
        ]);

        $this->getJson('/api/startup-ads')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id', $active->id)
            ->assertJsonPath('data.type', null)
            ->assertJsonPath('data.is_active', true);

        $this->getJson('/api/startup-ads?type=null')
            ->assertOk()
            ->assertJsonPath('data.id', $active->id)
            ->assertJsonPath('data.type', null);
    }

    public function test_api_filters_active_ads_by_requested_type(): void
    {
        StartupAd::query()->delete();
        $inspection = StartupAd::query()->create([
            'name' => 'Inspection Active Ad',
            'image' => 'startup-ads/inspection.webp',
            'type' => StartupAd::TYPE_INSPECTION,
            'is_active' => true,
        ]);
        StartupAd::query()->create([
            'name' => 'Inspection Inactive Ad',
            'image' => 'startup-ads/inspection-inactive.webp',
            'type' => StartupAd::TYPE_INSPECTION,
            'is_active' => false,
        ]);

        $this->getJson('/api/startup-ads?type=inspection')
            ->assertOk()
            ->assertJsonPath('data.id', $inspection->id)
            ->assertJsonPath('data.type', 'inspection')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_api_returns_null_when_no_active_ad_matches(): void
    {
        StartupAd::query()->delete();

        $this->getJson('/api/startup-ads?type=inspection')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('message', 'No active ad is available.')
            ->assertJsonPath('data', null);
    }
}
