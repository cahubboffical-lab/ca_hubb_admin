<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarFinanceBank;
use App\Models\CarFinanceRequest;
use App\Models\CarModel;
use App\Services\CarFinanceCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class CarFinanceController extends Controller
{
    public function banks(): JsonResponse
    {
        $banks = CarFinanceBank::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CarFinanceBank $bank) => [
                'id' => $bank->id,
                'code' => $bank->code,
                'name' => $bank->name,
                'finance_rate' => $bank->finance_rate,
                'insurance_rate' => $bank->insurance_rate,
                'processing_fee' => $bank->processing_fee,
                'logo_url' => $bank->logo_url,
                'accent_color' => $bank->accent_color,
            ])->values();

        return response()->json([
            'error' => false,
            'message' => $banks->isEmpty()
                ? __('Car finance plans are currently unavailable.')
                : __('Car finance banks fetched successfully.'),
            'data' => [
                'banks' => $banks,
                'tenure_options' => CarFinanceRequest::TENURE_OPTIONS,
                'down_payment_options' => CarFinanceRequest::DOWN_PAYMENT_OPTIONS,
                'new_car_fallback_price' => CarFinanceRequest::NEW_CAR_FALLBACK_PRICE,
                'currency_code' => 'PKR',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (! $user) {
            return response()->json([
                'error' => true,
                'message' => __('Please sign in to submit a car finance request.'),
            ], 401);
        }

        $request->merge([
            'car_variant' => $request->filled('car_variant') ? trim((string) $request->car_variant) : null,
        ]);
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();
            $result = DB::transaction(function () use ($validated, $user) {
                $bank = CarFinanceBank::query()->whereKey($validated['bank_id'])->where('is_active', true)->firstOrFail();
                $carModel = CarModel::query()->findOrFail($validated['car_model_id']);

                $existingRequest = CarFinanceRequest::query()
                    ->where('user_id', $user->id)
                    ->where('car_model_id', $carModel->id)
                    ->where('car_finance_bank_id', $bank->id)
                    ->where('finance_type', $validated['finance_type'])
                    ->where('tenure_years', $validated['tenure_years'])
                    ->where('down_payment_percent', $validated['down_payment_percent'])
                    ->whereIn('status', [CarFinanceRequest::STATUS_PENDING, CarFinanceRequest::STATUS_IN_PROGRESS])
                    ->latest('id')
                    ->first();

                if ($existingRequest) {
                    return ['request' => $existingRequest, 'created' => false];
                }

                $isUsedCar = $validated['finance_type'] === CarFinanceRequest::TYPE_USED;
                $modelPrice = (int) ($carModel->price ?? 0);
                $vehiclePrice = $isUsedCar
                    ? (int) $validated['used_car_price']
                    : ($modelPrice > 0 ? $modelPrice : CarFinanceRequest::NEW_CAR_FALLBACK_PRICE);
                $priceSource = $isUsedCar
                    ? 'customer_input'
                    : ($modelPrice > 0 ? 'car_model' : 'temporary_fallback');
                $calculation = CarFinanceCalculator::calculate(
                    $vehiclePrice,
                    (int) $validated['tenure_years'],
                    (float) $validated['down_payment_percent'],
                    $bank
                );

                $financeRequest = CarFinanceRequest::create(array_merge($calculation, [
                    'user_id' => $user->id,
                    'car_finance_bank_id' => $bank->id,
                    'city_id' => $validated['city_id'],
                    'car_model_id' => $carModel->id,
                    'finance_type' => $validated['finance_type'],
                    'model_year' => $isUsedCar ? $validated['model_year'] : null,
                    'car_variant' => $isUsedCar ? $validated['car_variant'] : null,
                    'used_car_price' => $isUsedCar ? $validated['used_car_price'] : null,
                    'vehicle_price' => $vehiclePrice,
                    'price_source' => $priceSource,
                    'tenure_years' => $validated['tenure_years'],
                    'down_payment_percent' => $validated['down_payment_percent'],
                    'status' => CarFinanceRequest::STATUS_PENDING,
                ]));

                return ['request' => $financeRequest, 'created' => true];
            });

            $financeRequest = $result['request']->load('bank:id,code,name');

            return response()->json([
                'error' => false,
                'message' => $result['created']
                    ? __('Car finance request submitted successfully.')
                    : __('An active car finance request already exists with the same bank and finance terms.'),
                'data' => $this->responseData($financeRequest),
            ], $result['created'] ? 201 : 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => __('Unable to submit the car finance request. Please try again.'),
            ], 500);
        }
    }

    private function rules(): array
    {
        return [
            'finance_type' => ['required', Rule::in([CarFinanceRequest::TYPE_NEW, CarFinanceRequest::TYPE_USED])],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'car_model_id' => ['required', 'integer', 'exists:car_models,id'],
            'bank_id' => ['required', 'integer', Rule::exists('car_finance_banks', 'id')->where('is_active', true)],
            'tenure_years' => ['required', 'integer', Rule::in(CarFinanceRequest::TENURE_OPTIONS)],
            'down_payment_percent' => ['required', 'numeric', Rule::in(CarFinanceRequest::DOWN_PAYMENT_OPTIONS)],
            'model_year' => ['required_if:finance_type,used_car', 'prohibited_if:finance_type,new_car', 'nullable', 'integer', 'min:1990', 'max:'.Carbon::now('Asia/Karachi')->year],
            'car_variant' => ['required_if:finance_type,used_car', 'prohibited_if:finance_type,new_car', 'nullable', 'string', 'max:150'],
            'used_car_price' => ['required_if:finance_type,used_car', 'prohibited_if:finance_type,new_car', 'nullable', 'integer', 'min:1'],
            'user_id' => ['prohibited'],
            'vehicle_price' => ['prohibited'],
            'price_source' => ['prohibited'],
            'finance_rate' => ['prohibited'],
            'insurance_rate' => ['prohibited'],
            'processing_fee' => ['prohibited'],
            'down_payment_amount' => ['prohibited'],
            'bank_loan' => ['prohibited'],
            'first_year_insurance' => ['prohibited'],
            'monthly_installment' => ['prohibited'],
            'total_initial_deposit' => ['prohibited'],
            'status' => ['prohibited'],
            'admin_notes' => ['prohibited'],
        ];
    }

    private function responseData(CarFinanceRequest $request): array
    {
        return [
            'id' => $request->id,
            'finance_type' => $request->finance_type,
            'city_id' => $request->city_id,
            'car_model_id' => $request->car_model_id,
            'bank' => [
                'id' => $request->bank->id,
                'code' => $request->bank->code,
                'name' => $request->bank->name,
            ],
            'vehicle_price' => $request->vehicle_price,
            'price_source' => $request->price_source,
            'tenure_years' => $request->tenure_years,
            'down_payment_percent' => (float) $request->down_payment_percent,
            'finance_rate' => $request->finance_rate,
            'insurance_rate' => $request->insurance_rate,
            'processing_fee' => $request->processing_fee,
            'down_payment_amount' => $request->down_payment_amount,
            'bank_loan' => $request->bank_loan,
            'first_year_insurance' => $request->first_year_insurance,
            'monthly_installment' => $request->monthly_installment,
            'total_initial_deposit' => $request->total_initial_deposit,
            'status' => $request->status,
            'created_at' => $request->created_at?->toIso8601String(),
        ];
    }
}
