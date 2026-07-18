<?php

namespace App\Models;

class CarOwnershipRequest extends VehicleServiceRequest
{
    protected $table = 'car_ownership_requests';

    public function serviceLabel(): string
    {
        return __('Car Ownership Transfer');
    }
}
