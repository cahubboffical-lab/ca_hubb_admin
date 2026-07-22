<?php

namespace Tests\Feature;

use App\Models\CarInspectionRequest;
use App\Models\CarModel;
use App\Models\CarRegistrationRequest;
use App\Models\City;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyServiceBookingApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/my-service-bookings')->assertUnauthorized();
    }

    public function test_endpoint_returns_only_authenticated_users_bookings(): void
    {
        $user = User::query()->firstOrFail();
        $otherUser = User::query()->where('id', '!=', $user->id)->first() ?? $user;
        $city = City::query()->firstOrFail();
        $carModel = CarModel::query()->firstOrFail();

        $inspection = CarInspectionRequest::create([
            'user_id' => $user->id,
            'full_name' => 'Booking Test User',
            'phone_number' => '+923001234567',
            'city_id' => $city->id,
            'car_model_id' => $carModel->id,
            'model_year' => 2022,
            'car_variant' => 'GLX',
            'car_condition' => 'used',
            'visit_area' => 'Gulberg',
            'visit_date' => Carbon::now('Asia/Karachi')->addDay()->toDateString(),
            'visit_start_time' => '10:00:00',
            'visit_end_time' => '11:00:00',
            'status' => 'pending',
        ]);

        CarRegistrationRequest::create([
            'user_id' => $otherUser->id,
            'full_name' => 'Other User',
            'phone_number' => '+923009999999',
            'phone_number_normalized' => '923009999999',
            'is_filer' => true,
            'car_model_id' => $carModel->id,
            'model_year' => 2021,
            'car_variant' => 'Automatic',
            'registration_place' => 'Punjab',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user, [], 'sanctum');

        $response = $this->getJson('/api/my-service-bookings?per_page=10')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.bookings.0.booking_key', 'car_inspection:'.$inspection->id)
            ->assertJsonPath('data.bookings.0.type', 'car_inspection')
            ->assertJsonPath('data.bookings.0.status', 'pending')
            ->assertJsonPath('data.bookings.0.schedule.start_time', '10:00:00');

        if ($otherUser->id !== $user->id) {
            $response->assertJsonCount(1, 'data.bookings');
        }
    }

    public function test_all_service_submission_endpoints_require_authentication(): void
    {
        foreach ([
            '/api/car-inspection-requests',
            '/api/sell-for-me-requests',
            '/api/car-registration-requests',
            '/api/car-ownership-requests',
            '/api/car-finance-requests',
            '/api/auction-sheet-verification-requests',
        ] as $endpoint) {
            $this->postJson($endpoint, [])->assertUnauthorized();
        }
    }
}
