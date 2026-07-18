<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreVehicleServiceRequest;
use App\Models\CarRegistrationRequest;

class CarRegistrationRequestController extends VehicleServiceRequestController
{
    public function store(StoreVehicleServiceRequest $request)
    {
        return $this->createRequest($request);
    }

    protected function modelClass(): string
    {
        return CarRegistrationRequest::class;
    }

    protected function successMessage(): string
    {
        return __('Car registration request submitted successfully.');
    }

    protected function errorMessage(): string
    {
        return __('Unable to submit the car registration request. Please try again.');
    }
}
