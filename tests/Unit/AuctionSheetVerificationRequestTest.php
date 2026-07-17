<?php

namespace Tests\Unit;

use App\Models\AuctionSheetVerificationRequest;
use PHPUnit\Framework\TestCase;

class AuctionSheetVerificationRequestTest extends TestCase
{
    public function test_chassis_number_is_normalized_for_storage_and_duplicate_checks(): void
    {
        self::assertSame(
            'NCP165-1234567',
            AuctionSheetVerificationRequest::normalizeChassisNumber(' ncp165 – 1234567 ')
        );
    }

    public function test_phone_number_is_normalized_for_duplicate_checks(): void
    {
        self::assertSame('923001234567', AuctionSheetVerificationRequest::normalizePhoneNumber('+92 300-1234567'));
    }
}
