<?php

namespace Tests\Feature;

use App\Models\CarModel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleServiceRequestApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::query()->firstOrFail(), [], 'sanctum');
    }

    /** @dataProvider endpoints */
    public function test_endpoints_return_structured_validation_errors(string $endpoint): void
    {
        $this->postJson($endpoint, [])
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonValidationErrors([
                'full_name',
                'phone_number',
                'is_filer',
                'car_model_id',
                'model_year',
                'car_variant',
                'registration_place',
            ]);
    }

    /** @dataProvider endpoints */
    public function test_submission_creates_one_request_and_reuses_an_active_duplicate(string $endpoint): void
    {
        $carModel = CarModel::query()->firstOrCreate(
            ['name' => 'Vehicle API Test Model', 'brand_name' => 'Codex'],
            ['price' => 0]
        );
        $payload = [
            'full_name' => 'Vehicle API Test User',
            'phone_number' => '+92 300-7654321',
            'is_filer' => true,
            'car_model_id' => $carModel->id,
            'model_year' => now()->year,
            'car_variant' => 'Automatic',
            'registration_place' => 'Punjab',
        ];

        $created = $this->postJson($endpoint, $payload)
            ->assertCreated()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.is_filer', true);

        $payload['phone_number'] = '923007654321';
        $this->postJson($endpoint, $payload)
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id', $created->json('data.id'));
    }

    public static function endpoints(): array
    {
        return [
            'car registration' => ['/api/car-registration-requests'],
            'car ownership' => ['/api/car-ownership-requests'],
        ];
    }
}
