<?php

namespace App\Http\Requests;

use App\Models\ServicePackage;
use Illuminate\Validation\Rule;

class StoreSellForMeRequest extends StoreServiceRequestRequest
{
    protected function serviceType(): string
    {
        return ServicePackage::TYPE_SELL_FOR_ME;
    }

    public function rules(): array
    {
        return parent::rules() + [
            'registration_area' => ['required', 'string', Rule::in(['Punjab', 'KPK', 'Sindh', 'Balochistan', 'AJK'])],
        ];
    }
}
