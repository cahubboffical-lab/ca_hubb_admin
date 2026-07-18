<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreVehicleServiceRequest;
use App\Models\CarOwnershipRequest;

class CarOwnershipRequestController extends VehicleServiceRequestController
{
    public function store(StoreVehicleServiceRequest $request)
    {
        return $this->createRequest($request);
    }

    protected function modelClass(): string
    {
        return CarOwnershipRequest::class;
    }

    protected function successMessage(): string
    {
        return __('Car ownership request submitted successfully.');
    }

    protected function errorMessage(): string
    {
        return __('Unable to submit the car ownership request. Please try again.');
    }
}
