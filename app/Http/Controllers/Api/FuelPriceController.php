<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelPrice;
use Illuminate\Http\JsonResponse;

class FuelPriceController extends Controller
{
    public function latest(): JsonResponse
    {
        $fuelPrice = FuelPrice::query()->latest('created_at')->latest('id')->first();

        if (! $fuelPrice) {
            return response()->json([
                'error' => true,
                'message' => __('Fuel prices are not available yet.'),
                'data' => null,
            ], 404);
        }

        return response()->json([
            'error' => false,
            'message' => __('Latest fuel prices fetched successfully.'),
            'data' => [
                'id' => $fuelPrice->id,
                'petrol_super' => $fuelPrice->petrol_super,
                'high_octane' => $fuelPrice->high_octane,
                'high_speed_diesel' => $fuelPrice->high_speed_diesel,
                'lpg' => $fuelPrice->lpg,
                'kerosene_oil' => $fuelPrice->kerosene_oil,
                'created_date' => $fuelPrice->created_at?->format('Y-m-d'),
                'created_at' => $fuelPrice->created_at?->toIso8601String(),
            ],
        ]);
    }
}
