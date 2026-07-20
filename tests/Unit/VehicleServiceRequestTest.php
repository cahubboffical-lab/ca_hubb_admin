<?php

namespace Tests\Unit;

use App\Models\CarOwnershipRequest;
use App\Models\CarRegistrationRequest;
use App\Models\VehicleServiceRequest;
use PHPUnit\Framework\TestCase;

class VehicleServiceRequestTest extends TestCase
{
    public function test_phone_number_normalization_supports_duplicate_detection(): void
    {
        $this->assertSame('923001234567', VehicleServiceRequest::normalizePhoneNumber('+92 (300) 123-4567'));
    }

    public function test_both_request_models_follow_the_same_strict_status_progression(): void
    {
        foreach ([new CarRegistrationRequest(), new CarOwnershipRequest()] as $request) {
            $request->status = VehicleServiceRequest::STATUS_PENDING;
            $this->assertSame(VehicleServiceRequest::STATUS_IN_PROGRESS, $request->nextStatus());

            $request->status = VehicleServiceRequest::STATUS_IN_PROGRESS;
            $this->assertSame(VehicleServiceRequest::STATUS_COMPLETED, $request->nextStatus());

            $request->status = VehicleServiceRequest::STATUS_COMPLETED;
            $this->assertNull($request->nextStatus());

            $request->status = VehicleServiceRequest::STATUS_PENDING;
            $this->assertTrue($request->canCancel());
            $request->status = VehicleServiceRequest::STATUS_CANCELED;
            $this->assertFalse($request->canCancel());
        }
    }
}
