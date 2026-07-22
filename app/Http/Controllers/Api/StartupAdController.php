<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StartupAd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StartupAdController extends Controller
{
    public function random(Request $request): JsonResponse
    {
        $type = mb_strtolower(trim((string) $request->query('type', '')));
        $type = in_array($type, ['', 'null'], true) ? null : $type;

        $validator = Validator::make(['type' => $type], [
            'type' => ['nullable', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $ad = StartupAd::query()
            ->where('is_active', true)
            ->when(
                $type === null,
                fn ($query) => $query->whereNull('type'),
                fn ($query) => $query->where('type', $type)
            )
            ->inRandomOrder()
            ->first();

        return response()->json([
            'error' => false,
            'message' => $ad ? __('Ad fetched successfully.') : __('No active ad is available.'),
            'data' => $ad ? [
                'id' => $ad->id,
                'name' => $ad->name,
                'image' => $ad->image,
                'url' => $ad->url,
                'type' => $ad->type,
                'is_active' => $ad->is_active,
                'created_at' => $ad->created_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
