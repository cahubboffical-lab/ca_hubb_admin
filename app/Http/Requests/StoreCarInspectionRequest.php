<?php

namespace App\Http\Requests;

use App\Models\ServicePackage;

class StoreCarInspectionRequest extends StoreServiceRequestRequest
{
    protected function serviceType(): string
    {
        return ServicePackage::TYPE_CAR_INSPECTION;
    }
}
