<?php

namespace App\Http\Requests;

use App\Models\AuctionSheetVerificationRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAuctionSheetVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chassis_number' => AuctionSheetVerificationRequest::normalizeChassisNumber((string) $this->input('chassis_number')),
            'phone_number' => is_string($this->input('phone_number')) ? trim($this->input('phone_number')) : $this->input('phone_number'),
        ]);
    }

    public function rules(): array
    {
        return [
            'chassis_number' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9()\-\s]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'chassis_number.regex' => __('Please enter a valid Japanese chassis number.'),
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
