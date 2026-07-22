<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\City;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceRequestApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::query()->firstOrFail(), [], 'sanctum');
    }

    /**
     * @dataProvider serviceRequestEndpoints
     */
    public function test_each_service_endpoint_returns_structured_validation_errors(string $endpoint, array $expectedErrors): void
    {
        $response = $this->postJson($endpoint, []);

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonValidationErrors($expectedErrors);
    }

    public function serviceRequestEndpoints(): array
    {
        $commonErrors = [
            'full_name',
            'phone_number',
            'city_id',
            'car_model_id',
            'model_year',
            'car_variant',
            'car_condition',
            'visit_area',
            'visit_date',
            'visit_start_time',
            'visit_end_time',
        ];

        return [
            'car inspection' => ['/api/car-inspection-requests', $commonErrors],
            'sell for me' => ['/api/sell-for-me-requests', [...$commonErrors, 'registration_area']],
        ];
    }

    /**
     * @dataProvider serviceRequestEndpoints
     */
    public function test_service_package_is_optional(string $endpoint, array $expectedErrors): void
    {
        $response = $this->postJson($endpoint, []);

        $response->assertJsonMissingValidationErrors('service_package_id');
    }

    public function test_car_inspection_does_not_accept_or_return_registration_area(): void
    {
        $response = $this->postJson('/api/car-inspection-requests', [
            'full_name' => 'Inspection Customer',
            'phone_number' => '+923001234567',
            'city_id' => City::query()->value('id'),
            'car_model_id' => CarModel::query()->value('id'),
            'model_year' => 2022,
            'car_variant' => 'GLX',
            'car_condition' => 'used',
            'registration_area' => 'Punjab',
            'visit_area' => 'Gulberg',
            'visit_date' => Carbon::now('Asia/Karachi')->addDay()->format('Y-m-d'),
            'visit_start_time' => '10:00:00',
            'visit_end_time' => '11:00:00',
        ]);

        $response
            ->assertCreated()
            ->assertJsonMissingPath('data.registration_area');
    }
}
