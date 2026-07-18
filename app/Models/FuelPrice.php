<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelPrice extends Model
{
    use HasFactory;

    public const PRICE_FIELDS = [
        'petrol_super',
        'high_octane',
        'high_speed_diesel',
        'lpg',
        'kerosene_oil',
    ];

    protected $fillable = self::PRICE_FIELDS;

    protected $casts = [
        'petrol_super' => 'decimal:2',
        'high_octane' => 'decimal:2',
        'high_speed_diesel' => 'decimal:2',
        'lpg' => 'decimal:2',
        'kerosene_oil' => 'decimal:2',
    ];
}
