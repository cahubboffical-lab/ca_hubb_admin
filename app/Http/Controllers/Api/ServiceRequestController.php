<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Models\ServiceRequest;
use App\Models\ServicePackage;
use Throwable;

abstract class ServiceRequestController extends Controller
{
    /** @return class-string<ServiceRequest> */
    abstract protected function modelClass(): string;

    protected function createRequest(StoreServiceRequestRequest $request)
    {
        try {
            $modelClass = $this->modelClass();
            /** @var ServiceRequest $serviceRequest */
            $serviceRequest = $modelClass::create($request->validated() + [
                'user_id' => $request->user('sanctum')?->id,
                'status' => ServiceRequest::STATUS_PENDING,
            ]);

            return response()->json([
                'error' => false,
                'message' => __('Service request submitted successfully.'),
                'data' => $this->responseData($serviceRequest),
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => __('Unable to submit the service request. Please try again.'),
            ], 500);
        }
    }

    private function responseData(ServiceRequest $serviceRequest): array
    {
        $data = [
            'id' => $serviceRequest->id,
            'service_type' => $serviceRequest->serviceType(),
            'service_package_id' => $serviceRequest->service_package_id,
            'full_name' => $serviceRequest->full_name,
            'phone_number' => $serviceRequest->phone_number,
            'city_id' => $serviceRequest->city_id,
            'car_model_id' => $serviceRequest->car_model_id,
            'model_year' => $serviceRequest->model_year,
            'car_variant' => $serviceRequest->car_variant,
            'car_condition' => $serviceRequest->car_condition,
            'visit_area' => $serviceRequest->visit_area,
            'visit_date' => $serviceRequest->visit_date?->format('Y-m-d'),
            'visit_start_time' => $serviceRequest->visit_start_time,
            'visit_end_time' => $serviceRequest->visit_end_time,
            'status' => $serviceRequest->status,
            'created_at' => $serviceRequest->created_at?->toIso8601String(),
        ];

        if ($serviceRequest->serviceType() === ServicePackage::TYPE_SELL_FOR_ME) {
            $data['registration_area'] = $serviceRequest->registration_area;
        }

        return $data;
    }
}
