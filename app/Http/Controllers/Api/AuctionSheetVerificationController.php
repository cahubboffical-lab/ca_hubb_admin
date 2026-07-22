<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAuctionSheetVerificationRequest;
use App\Models\AuctionSheetVerificationPrice;
use App\Models\AuctionSheetVerificationRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuctionSheetVerificationController extends Controller
{
    public function price()
    {
        try {
            $price = AuctionSheetVerificationPrice::current();

            return response()->json([
                'error' => false,
                'message' => __('Auction sheet verification price fetched successfully.'),
                'data' => [
                    'price_amount' => $price->price_amount,
                    'currency_code' => $price->currency_code,
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => __('Unable to fetch the auction sheet verification price. Please try again.'),
            ], 500);
        }
    }

    public function store(StoreAuctionSheetVerificationRequest $request)
    {
        try {
            $user = $request->user();
            $result = DB::transaction(function () use ($request, $user) {
                $phoneNumberNormalized = AuctionSheetVerificationRequest::normalizePhoneNumber($request->string('phone_number')->toString());
                $existingRequest = AuctionSheetVerificationRequest::query()
                    ->where('user_id', $user->id)
                    ->where('chassis_number', $request->string('chassis_number')->toString())
                    ->where('phone_number_normalized', $phoneNumberNormalized)
                    ->where('status', AuctionSheetVerificationRequest::STATUS_PENDING)
                    ->latest('id')
                    ->first();

                if ($existingRequest) {
                    return ['request' => $existingRequest, 'created' => false];
                }

                $price = AuctionSheetVerificationPrice::current();
                $verificationRequest = AuctionSheetVerificationRequest::create([
                    'user_id' => $user->id,
                    'chassis_number' => $request->string('chassis_number')->toString(),
                    'phone_number' => $request->string('phone_number')->toString(),
                    'phone_number_normalized' => $phoneNumberNormalized,
                    'status' => AuctionSheetVerificationRequest::STATUS_PENDING,
                    'notification_status' => AuctionSheetVerificationRequest::NOTIFICATION_PENDING,
                    'price_amount' => $price->price_amount,
                    'currency_code' => $price->currency_code,
                ]);

                return ['request' => $verificationRequest, 'created' => true];
            });

            /** @var AuctionSheetVerificationRequest $verificationRequest */
            $verificationRequest = $result['request'];

            return response()->json([
                'error' => false,
                'message' => $result['created']
                    ? __('Auction sheet verification request submitted successfully.')
                    : __('An active verification request already exists for this chassis number and phone number.'),
                'data' => $this->responseData($verificationRequest),
            ], $result['created'] ? 201 : 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => __('Unable to submit the auction sheet verification request. Please try again.'),
            ], 500);
        }
    }

    private function responseData(AuctionSheetVerificationRequest $verificationRequest): array
    {
        return [
            'id' => $verificationRequest->id,
            'chassis_number' => $verificationRequest->chassis_number,
            'phone_number' => $verificationRequest->phone_number,
            'status' => $verificationRequest->status,
            'notification_status' => $verificationRequest->notification_status,
            'price_amount' => $verificationRequest->price_amount,
            'currency_code' => $verificationRequest->currency_code,
            'created_at' => $verificationRequest->created_at?->toIso8601String(),
        ];
    }
}
