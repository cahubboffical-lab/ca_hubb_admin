<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreSellForMeRequest;
use App\Models\SellForMeRequest;

class SellForMeRequestController extends ServiceRequestController
{
    public function store(StoreSellForMeRequest $request)
    {
        return $this->createRequest($request);
    }

    protected function modelClass(): string
    {
        return SellForMeRequest::class;
    }
}
