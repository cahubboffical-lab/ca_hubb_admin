<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCarFinanceBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? strtolower(trim($this->code)) : $this->code,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'logo_url' => $this->filled('logo_url') ? trim((string) $this->logo_url) : null,
            'accent_color' => $this->filled('accent_color') ? strtoupper(trim((string) $this->accent_color)) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $bankId = $this->route('carFinanceBank')?->id;

        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', Rule::unique('car_finance_banks', 'code')->ignore($bankId)],
            'name' => ['required', 'string', 'max:150'],
            'finance_rate' => ['required', 'numeric', 'min:0', 'max:999.9999', 'decimal:0,4'],
            'insurance_rate' => ['required', 'numeric', 'min:0', 'max:999.9999', 'decimal:0,4'],
            'processing_fee' => ['required', 'integer', 'min:0'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-F]{6}$/'],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
