<?php

namespace App\Http\Requests;

use App\Models\FuelPrice;
use Illuminate\Foundation\Http\FormRequest;

class SaveFuelPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return collect(FuelPrice::PRICE_FIELDS)
            ->mapWithKeys(fn (string $field) => [
                $field => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            ])->all();
    }
}
