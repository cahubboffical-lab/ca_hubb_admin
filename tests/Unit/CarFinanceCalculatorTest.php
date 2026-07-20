<?php

namespace Tests\Unit;

use App\Models\CarFinanceBank;
use App\Models\CarFinanceRequest;
use App\Services\CarFinanceCalculator;
use PHPUnit\Framework\TestCase;

class CarFinanceCalculatorTest extends TestCase
{
    public function test_calculation_matches_the_mobile_finance_formula(): void
    {
        $bank = new CarFinanceBank([
            'finance_rate' => 15.6400,
            'insurance_rate' => 1.5000,
            'processing_fee' => 12000,
        ]);

        $result = CarFinanceCalculator::calculate(5000000, 3, 40, $bank);

        self::assertSame(2000000, $result['down_payment_amount']);
        self::assertSame(3000000, $result['bank_loan']);
        self::assertSame(75000, $result['first_year_insurance']);
        self::assertSame(122433, $result['monthly_installment']);
        self::assertSame(2087000, $result['total_initial_deposit']);
    }

    public function test_finance_request_can_only_be_canceled_while_active(): void
    {
        $request = new CarFinanceRequest(['status' => CarFinanceRequest::STATUS_PENDING]);
        self::assertTrue($request->canCancel());

        $request->status = CarFinanceRequest::STATUS_IN_PROGRESS;
        self::assertTrue($request->canCancel());

        $request->status = CarFinanceRequest::STATUS_CANCELED;
        self::assertFalse($request->canCancel());

        $request->status = CarFinanceRequest::STATUS_COMPLETED;
        self::assertFalse($request->canCancel());
    }
}
