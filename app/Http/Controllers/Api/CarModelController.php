<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarModelResource;
use App\Models\CarModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CarModelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => ['nullable', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'brand_name', 'price', 'created_at', 'updated_at'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'data' => null,
                'code' => config('constants.RESPONSE_CODE.VALIDATION_ERROR'),
            ], 422);
        }

        $query = CarModel::query();

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        if ($request->filled('brand_name')) {
            $query->where('brand_name', $request->string('brand_name')->toString());
        }

        $sortBy = $request->input('sort_by', 'brand_name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->sort($sortBy, $sortOrder);

        if ($sortBy === 'brand_name') {
            $query->orderBy('car_models.name', $sortOrder);
        }

        $carModels = $query->get();

        return response()->json([
            'error' => false,
            'message' => __('Car Models Fetched Successfully'),
            'data' => CarModelResource::collection($carModels)->resolve($request),
            'code' => config('constants.RESPONSE_CODE.SUCCESS'),
        ]);
    }
}
