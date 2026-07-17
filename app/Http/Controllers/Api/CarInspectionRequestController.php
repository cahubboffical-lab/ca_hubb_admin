<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCarInspectionRequest;
use App\Models\CarInspectionRequest;

class CarInspectionRequestController extends ServiceRequestController
{
    public function store(StoreCarInspectionRequest $request)
    {
        return $this->createRequest($request);
    }

    protected function modelClass(): string
    {
        return CarInspectionRequest::class;
    }
}
