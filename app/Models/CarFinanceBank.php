<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarFinanceBank extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'finance_rate',
        'insurance_rate',
        'processing_fee',
        'logo_url',
        'accent_color',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'finance_rate' => 'decimal:4',
        'insurance_rate' => 'decimal:4',
        'processing_fee' => 'integer',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(CarFinanceRequest::class);
    }
}
