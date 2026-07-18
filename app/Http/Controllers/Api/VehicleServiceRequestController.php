<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleServiceRequest;
use App\Models\VehicleServiceRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class VehicleServiceRequestController extends Controller
{
    /** @return class-string<VehicleServiceRequest> */
    abstract protected function modelClass(): string;

    abstract protected function successMessage(): string;

    abstract protected function errorMessage(): string;

    protected function createRequest(StoreVehicleServiceRequest $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $modelClass = $this->modelClass();
                $phoneNumberNormalized = VehicleServiceRequest::normalizePhoneNumber($request->string('phone_number')->toString());

                /** @var VehicleServiceRequest|null $existingRequest */
                $existingRequest = $modelClass::query()
                    ->where('phone_number_normalized', $phoneNumberNormalized)
                    ->where('car_model_id', $request->integer('car_model_id'))
                    ->where('model_year', $request->integer('model_year'))
                    ->where('registration_place', $request->string('registration_place')->toString())
                    ->whereIn('status', [
                        VehicleServiceRequest::STATUS_PENDING,
                        VehicleServiceRequest::STATUS_IN_PROGRESS,
                    ])
                    ->latest('id')
                    ->first();

                if ($existingRequest) {
                    return ['request' => $existingRequest, 'created' => false];
                }

                /** @var VehicleServiceRequest $serviceRequest */
                $serviceRequest = $modelClass::create([
                    'user_id' => $request->user('sanctum')?->id,
                    'full_name' => $request->string('full_name')->toString(),
                    'phone_number' => $request->string('phone_number')->toString(),
                    'phone_number_normalized' => $phoneNumberNormalized,
                    'is_filer' => $request->boolean('is_filer'),
                    'car_model_id' => $request->integer('car_model_id'),
                    'model_year' => $request->integer('model_year'),
                    'car_variant' => $request->string('car_variant')->toString(),
                    'registration_place' => $request->string('registration_place')->toString(),
                    'status' => VehicleServiceRequest::STATUS_PENDING,
                ]);

                return ['request' => $serviceRequest, 'created' => true];
            });

            /** @var VehicleServiceRequest $serviceRequest */
            $serviceRequest = $result['request'];

            return response()->json([
                'error' => false,
                'message' => $result['created']
                    ? $this->successMessage()
                    : __('An active request already exists with the same phone number and vehicle details.'),
                'data' => $this->responseData($serviceRequest),
            ], $result['created'] ? 201 : 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => $this->errorMessage(),
            ], 500);
        }
    }

    private function responseData(VehicleServiceRequest $serviceRequest): array
    {
        return [
            'id' => $serviceRequest->id,
            'full_name' => $serviceRequest->full_name,
            'phone_number' => $serviceRequest->phone_number,
            'is_filer' => $serviceRequest->is_filer,
            'car_model_id' => $serviceRequest->car_model_id,
            'model_year' => $serviceRequest->model_year,
            'car_variant' => $serviceRequest->car_variant,
            'registration_place' => $serviceRequest->registration_place,
            'status' => $serviceRequest->status,
            'created_at' => $serviceRequest->created_at?->toIso8601String(),
        ];
    }
}
