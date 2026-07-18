<?php

namespace App\Models;

class CarRegistrationRequest extends VehicleServiceRequest
{
    protected $table = 'car_registration_requests';

    public function serviceLabel(): string
    {
        return __('Car Registration');
    }
}
