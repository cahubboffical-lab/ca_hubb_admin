<?php

namespace Tests\Feature;

use App\Models\FuelPrice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FuelPriceApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_latest_endpoint_returns_the_newest_price_snapshot_with_dates(): void
    {
        FuelPrice::create([
            'petrol_super' => 270.10,
            'high_octane' => 300.20,
            'high_speed_diesel' => 280.30,
            'lpg' => 250.40,
            'kerosene_oil' => 190.50,
        ])->update(['created_at' => now()->subDay()]);

        $latest = FuelPrice::create([
            'petrol_super' => 271.31,
            'high_octane' => 301.41,
            'high_speed_diesel' => 281.51,
            'lpg' => 251.61,
            'kerosene_oil' => 191.71,
        ]);

        $this->getJson('/api/fuel-prices/latest')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id', $latest->id)
            ->assertJsonPath('data.petrol_super', '271.31')
            ->assertJsonPath('data.high_octane', '301.41')
            ->assertJsonPath('data.high_speed_diesel', '281.51')
            ->assertJsonPath('data.lpg', '251.61')
            ->assertJsonPath('data.kerosene_oil', '191.71')
            ->assertJsonStructure(['data' => ['created_date', 'created_at']]);
    }
}
