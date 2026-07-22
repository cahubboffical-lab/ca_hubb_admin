<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreVehicleServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect(['full_name', 'phone_number', 'car_variant', 'registration_place'])
            ->mapWithKeys(fn (string $field) => [
                $field => is_string($this->input($field)) ? trim($this->input($field)) : $this->input($field),
            ])->all());
    }

    public function rules(): array
    {
        return [
            'user_id' => ['prohibited'],
            'full_name' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9()\-\s]+$/'],
            'is_filer' => ['required', 'boolean'],
            'car_model_id' => ['required', 'integer', 'exists:car_models,id'],
            'model_year' => ['required', 'integer', 'min:1990', 'max:'.Carbon::now('Asia/Karachi')->year],
            'car_variant' => ['required', 'string', 'max:150'],
            'registration_place' => ['required', 'string', Rule::in(['Punjab', 'KPK', 'Sindh', 'Balochistan', 'AJK'])],
        ];
    }

    public function messages(): array
    {
        return [
            'is_filer.required' => __('Please select filer status.'),
            'phone_number.regex' => __('Please enter a valid phone number.'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => true,
            'message' => __('The given data was invalid.'),
            'errors' => $validator->errors(),
        ], 422));
    }
}
