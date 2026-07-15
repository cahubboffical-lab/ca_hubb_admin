<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand_name' => $this->brand_name,
            'price' => $this->price,
            'created_at' => $this->created_at?->toISOString(),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->toISOString(),
            'updated_by' => $this->updated_by,
        ];
    }
}
