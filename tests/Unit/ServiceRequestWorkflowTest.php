<?php

namespace Tests\Unit;

use App\Models\CarInspectionRequest;
use App\Models\ServiceRequest;
use App\Models\SellForMeRequest;
use PHPUnit\Framework\TestCase;

class ServiceRequestWorkflowTest extends TestCase
{
    public function test_statuses_only_advance_in_the_required_order(): void
    {
        foreach ([new CarInspectionRequest(), new SellForMeRequest()] as $serviceRequest) {
            $serviceRequest->status = ServiceRequest::STATUS_PENDING;
            self::assertSame(ServiceRequest::STATUS_IN_PROGRESS, $serviceRequest->nextStatus());

            $serviceRequest->status = ServiceRequest::STATUS_IN_PROGRESS;
            self::assertSame(ServiceRequest::STATUS_COMPLETED, $serviceRequest->nextStatus());

            $serviceRequest->status = ServiceRequest::STATUS_COMPLETED;
            self::assertNull($serviceRequest->nextStatus());

            $serviceRequest->status = ServiceRequest::STATUS_PENDING;
            self::assertTrue($serviceRequest->canCancel());
            $serviceRequest->status = ServiceRequest::STATUS_CANCELED;
            self::assertFalse($serviceRequest->canCancel());
        }
    }
}
