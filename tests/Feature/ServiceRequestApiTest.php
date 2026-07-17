<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServiceRequestApiTest extends TestCase
{
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
}
