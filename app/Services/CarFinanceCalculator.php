<?php

namespace App\Services;

use App\Models\CarFinanceBank;

class CarFinanceCalculator
{
    public static function calculate(int $vehiclePrice, int $tenureYears, float $downPaymentPercent, CarFinanceBank $bank): array
    {
        $downPaymentAmount = (int) round($vehiclePrice * $downPaymentPercent / 100);
        $bankLoan = $vehiclePrice - $downPaymentAmount;
        $firstYearInsurance = (int) round($vehiclePrice * (float) $bank->insurance_rate / 100);
        $totalRepayable = $bankLoan * (1 + ((float) $bank->finance_rate / 100 * $tenureYears));
        $monthlyInstallment = (int) round($totalRepayable / ($tenureYears * 12));

        return [
            'finance_rate' => $bank->finance_rate,
            'insurance_rate' => $bank->insurance_rate,
            'processing_fee' => $bank->processing_fee,
            'down_payment_amount' => $downPaymentAmount,
            'bank_loan' => $bankLoan,
            'first_year_insurance' => $firstYearInsurance,
            'monthly_installment' => $monthlyInstallment,
            'total_initial_deposit' => $downPaymentAmount + $bank->processing_fee + $firstYearInsurance,
        ];
    }
}
