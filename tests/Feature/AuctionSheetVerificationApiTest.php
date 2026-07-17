<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuctionSheetVerificationApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_price_endpoint_returns_the_single_authoritative_price(): void
    {
        $this->getJson('/api/auction-sheet-verification-price')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.currency_code', 'PKR')
            ->assertJsonStructure(['data' => ['price_amount', 'currency_code']]);
    }

    public function test_create_endpoint_returns_structured_validation_errors(): void
    {
        $this->postJson('/api/auction-sheet-verification-requests', [])
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonValidationErrors(['chassis_number', 'phone_number']);
    }

    public function test_invalid_japanese_chassis_number_is_rejected(): void
    {
        $this->postJson('/api/auction-sheet-verification-requests', [
            'chassis_number' => 'INVALID ONLY LETTERS',
            'phone_number' => '+923001234567',
        ])->assertStatus(422)->assertJsonValidationErrors('chassis_number');
    }

    public function test_submission_is_normalized_and_duplicate_pending_request_is_reused(): void
    {
        $payload = [
            'chassis_number' => ' ncp165 – test123 ',
            'phone_number' => '+92 300-7654321',
        ];

        $created = $this->postJson('/api/auction-sheet-verification-requests', $payload)
            ->assertCreated()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.chassis_number', 'NCP165-TEST123')
            ->assertJsonPath('data.status', 'pending');

        $this->postJson('/api/auction-sheet-verification-requests', $payload)
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.id', $created->json('data.id'));
    }
}
