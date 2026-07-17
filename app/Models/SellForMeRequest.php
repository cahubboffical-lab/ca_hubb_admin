<?php

namespace App\Models;

class SellForMeRequest extends ServiceRequest
{
    protected $table = 'sell_for_me_requests';

    protected $fillable = [
        'user_id',
        'service_package_id',
        'full_name',
        'phone_number',
        'city_id',
        'car_model_id',
        'model_year',
        'car_variant',
        'car_condition',
        'registration_area',
        'visit_area',
        'visit_date',
        'visit_start_time',
        'visit_end_time',
        'status',
    ];

    public function serviceType(): string
    {
        return ServicePackage::TYPE_SELL_FOR_ME;
    }
}
