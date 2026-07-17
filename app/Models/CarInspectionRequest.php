<?php

namespace App\Models;

class CarInspectionRequest extends ServiceRequest
{
    protected $table = 'car_inspection_requests';

    public function serviceType(): string
    {
        return ServicePackage::TYPE_CAR_INSPECTION;
    }
}
