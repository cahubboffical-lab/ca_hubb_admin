<?php

namespace Tests\Feature;

use App\Models\AuctionSheetVerificationRequest;
use App\Models\CarFinanceBank;
use App\Models\CarFinanceRequest;
use App\Models\CarInspectionRequest;
use App\Models\CarModel;
use App\Models\CarRegistrationRequest;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminRequestNotesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_service_request_schema_matches_the_admin_workflow(): void
    {
        self::assertTrue(Schema::hasColumn('car_inspection_requests', 'admin_notes'));
        self::assertTrue(Schema::hasColumn('sell_for_me_requests', 'admin_notes'));
        self::assertFalse(Schema::hasColumn('car_inspection_requests', 'registration_area'));
        self::assertFalse(Schema::hasColumn('auction_sheet_verification_requests', 'report_url'));
    }

    public function test_admin_notes_are_required_and_saved_for_every_service_status_workflow(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $admin = User::query()->firstOrFail();
        $permissions = [
            'car-inspection-request-list',
            'car-registration-request-update',
            'auction-sheet-verification-request-update',
            'car-finance-request-update',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $admin->givePermissionTo($permissions);
        $this->actingAs($admin);

        $cityId = City::query()->value('id');
        $carModelId = CarModel::query()->value('id');

        $inspection = CarInspectionRequest::query()->create([
            'full_name' => 'Inspection Customer',
            'phone_number' => '+923001111111',
            'city_id' => $cityId,
            'car_model_id' => $carModelId,
            'model_year' => 2022,
            'car_variant' => 'GLX',
            'car_condition' => 'used',
            'visit_area' => 'Gulberg',
            'visit_date' => now()->addDay()->format('Y-m-d'),
            'visit_start_time' => '10:00:00',
            'visit_end_time' => '11:00:00',
            'status' => CarInspectionRequest::STATUS_PENDING,
        ]);
        $this->assertNotesRequiredAndSaved(
            route('service-requests.update-status', ['section' => 'car-inspection', 'requestId' => $inspection->id]),
            $inspection,
            CarInspectionRequest::STATUS_IN_PROGRESS
        );
        $this->getJson(route('service-requests.show', ['section' => 'car-inspection', 'requestId' => $inspection->id]))
            ->assertOk()
            ->assertJsonPath('data.admin_notes', 'Verified by the admin before changing the request status.')
            ->assertJsonMissingPath('data.registration_area');

        $registration = CarRegistrationRequest::query()->create([
            'full_name' => 'Registration Customer',
            'phone_number' => '+923002222222',
            'phone_number_normalized' => '923002222222',
            'is_filer' => true,
            'car_model_id' => $carModelId,
            'model_year' => 2021,
            'car_variant' => 'Grande',
            'registration_place' => 'Punjab',
            'status' => CarRegistrationRequest::STATUS_PENDING,
        ]);
        $this->assertNotesRequiredAndSaved(
            route('vehicle-service-requests.update-status', ['section' => 'car-registration', 'requestId' => $registration->id]),
            $registration,
            CarRegistrationRequest::STATUS_IN_PROGRESS
        );

        $auction = AuctionSheetVerificationRequest::query()->create([
            'chassis_number' => 'NCP165-NOTES123',
            'phone_number' => '+923003333333',
            'phone_number_normalized' => '923003333333',
            'status' => AuctionSheetVerificationRequest::STATUS_PENDING,
            'notification_status' => AuctionSheetVerificationRequest::NOTIFICATION_PENDING,
            'price_amount' => 2950,
            'currency_code' => 'PKR',
        ]);
        $this->assertNotesRequiredAndSaved(
            route('auction-sheet-verification.complete', $auction),
            $auction,
            null
        );

        $auctionToCancel = AuctionSheetVerificationRequest::query()->create([
            'chassis_number' => 'NCP165-CANCEL123',
            'phone_number' => '+923004444444',
            'phone_number_normalized' => '923004444444',
            'status' => AuctionSheetVerificationRequest::STATUS_PENDING,
            'notification_status' => AuctionSheetVerificationRequest::NOTIFICATION_PENDING,
            'price_amount' => 2950,
            'currency_code' => 'PKR',
        ]);
        $this->assertNotesRequiredAndSaved(
            route('auction-sheet-verification.cancel', $auctionToCancel),
            $auctionToCancel,
            null
        );

        $finance = CarFinanceRequest::query()->create([
            'user_id' => $admin->id,
            'car_finance_bank_id' => CarFinanceBank::query()->value('id'),
            'city_id' => $cityId,
            'car_model_id' => $carModelId,
            'finance_type' => CarFinanceRequest::TYPE_NEW,
            'vehicle_price' => 5000000,
            'price_source' => 'temporary_fallback',
            'tenure_years' => 3,
            'down_payment_percent' => 40,
            'finance_rate' => 15.64,
            'insurance_rate' => 1.5,
            'processing_fee' => 12000,
            'down_payment_amount' => 2000000,
            'bank_loan' => 3000000,
            'first_year_insurance' => 75000,
            'monthly_installment' => 122433,
            'total_initial_deposit' => 2087000,
            'status' => CarFinanceRequest::STATUS_PENDING,
        ]);
        $this->assertNotesRequiredAndSaved(
            route('car-finance-requests.update-status', $finance),
            $finance,
            CarFinanceRequest::STATUS_IN_PROGRESS
        );
    }

    private function assertNotesRequiredAndSaved(string $url, $model, ?string $status): void
    {
        $payload = $status === null ? [] : ['status' => $status];

        $this->patchJson($url, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('admin_notes');

        $payload['admin_notes'] = 'Verified by the admin before changing the request status.';
        $this->patchJson($url, $payload)
            ->assertOk()
            ->assertJsonPath('error', false);

        self::assertSame($payload['admin_notes'], $model->fresh()->admin_notes);
    }
}
