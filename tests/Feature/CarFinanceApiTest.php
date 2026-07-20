<?php

namespace Tests\Feature;

use App\Models\CarFinanceBank;
use App\Models\CarFinanceRequest;
use App\Models\CarModel;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarFinanceApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_banks_endpoint_returns_active_banks_and_finance_options(): void
    {
        $this->getJson('/api/car-finance-banks')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.currency_code', 'PKR')
            ->assertJsonPath('data.new_car_fallback_price', 5000000)
            ->assertJsonPath('data.tenure_options', [1, 2, 3, 4, 5])
            ->assertJsonPath('data.down_payment_options', [40, 45, 50, 55, 60, 65, 70])
            ->assertJsonStructure(['data' => ['banks' => [['id', 'code', 'name', 'finance_rate', 'insurance_rate', 'processing_fee']]]]);
    }

    public function test_finance_request_requires_authentication(): void
    {
        $this->postJson('/api/car-finance-requests', [])
            ->assertUnauthorized()
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Please sign in to submit a car finance request.');
    }

    public function test_new_car_request_uses_fallback_and_server_calculations_and_reuses_duplicate(): void
    {
        CarFinanceRequest::query()->delete();
        $user = User::query()->whereNotNull('mobile')->firstOrFail();
        Sanctum::actingAs($user, [], 'sanctum');
        $bank = CarFinanceBank::query()->where('code', 'faysal')->firstOrFail();
        $city = City::query()->firstOrFail();
        $carModel = CarModel::query()->where(fn ($query) => $query->whereNull('price')->orWhere('price', '<=', 0))->firstOrFail();
        $payload = [
            'finance_type' => 'new_car',
            'city_id' => $city->id,
            'car_model_id' => $carModel->id,
            'bank_id' => $bank->id,
            'tenure_years' => 3,
            'down_payment_percent' => 40,
        ];

        $created = $this->postJson('/api/car-finance-requests', $payload)
            ->assertCreated()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.vehicle_price', 5000000)
            ->assertJsonPath('data.price_source', 'temporary_fallback')
            ->assertJsonPath('data.down_payment_amount', 2000000)
            ->assertJsonPath('data.bank_loan', 3000000)
            ->assertJsonPath('data.first_year_insurance', 75000)
            ->assertJsonPath('data.monthly_installment', 122433)
            ->assertJsonPath('data.total_initial_deposit', 2087000)
            ->assertJsonPath('data.status', 'pending');

        $this->postJson('/api/car-finance-requests', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $created->json('data.id'));
    }

    public function test_used_car_fields_are_required_for_used_car_finance(): void
    {
        $user = User::query()->firstOrFail();
        Sanctum::actingAs($user, [], 'sanctum');

        $this->postJson('/api/car-finance-requests', [
            'finance_type' => 'used_car',
            'city_id' => City::query()->value('id'),
            'car_model_id' => CarModel::query()->value('id'),
            'bank_id' => CarFinanceBank::query()->where('is_active', true)->value('id'),
            'tenure_years' => 3,
            'down_payment_percent' => 40,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['model_year', 'car_variant', 'used_car_price']);
    }
}
