<?php

namespace App\Http\Requests;

use App\Models\ServicePackage;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

abstract class StoreServiceRequestRequest extends FormRequest
{
    abstract protected function serviceType(): string;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(collect([
            'full_name', 'phone_number', 'car_variant', 'car_condition', 'registration_area',
            'visit_area', 'visit_date', 'visit_start_time', 'visit_end_time',
        ])->mapWithKeys(fn (string $field) => [
            $field => is_string($this->input($field)) ? trim($this->input($field)) : $this->input($field),
        ])->all());
    }

    public function rules(): array
    {
        return [
            'service_package_id' => ['nullable', 'integer', 'exists:service_packages,id'],
            'full_name' => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:30'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'car_model_id' => ['required', 'integer', 'exists:car_models,id'],
            'model_year' => ['required', 'integer', 'min:1990', 'max:'.Carbon::now('Asia/Karachi')->year],
            'car_variant' => ['required', 'string', 'max:150'],
            'car_condition' => ['required', Rule::in(['used', 'new'])],
            'visit_area' => ['required', 'string', 'max:255'],
            'visit_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.Carbon::now('Asia/Karachi')->toDateString()],
            'visit_start_time' => ['required', 'date_format:H:i:s'],
            'visit_end_time' => ['required', 'date_format:H:i:s', 'after:visit_start_time'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('service_package_id') || $validator->errors()->has('service_package_id')) {
                return;
            }

            $packageType = ServicePackage::whereKey($this->integer('service_package_id'))->value('type');
            if ($packageType !== $this->serviceType()) {
                $validator->errors()->add('service_package_id', __('The selected package does not match this service.'));
            }
        });
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
