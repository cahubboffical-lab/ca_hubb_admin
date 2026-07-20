<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarFinanceRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_COMPLETED = 'completed';

    public const TYPE_NEW = 'new_car';
    public const TYPE_USED = 'used_car';

    public const NEW_CAR_FALLBACK_PRICE = 5000000;
    public const TENURE_OPTIONS = [1, 2, 3, 4, 5];
    public const DOWN_PAYMENT_OPTIONS = [40, 45, 50, 55, 60, 65, 70];

    protected $fillable = [
        'user_id', 'full_name', 'phone_number', 'email', 'cnic', 'income_source', 'monthly_income',
        'current_bank', 'has_credit_cards_or_loans', 'processing_time', 'car_finance_bank_id', 'city_id',
        'car_model_id', 'finance_type', 'model_year',
        'car_variant', 'used_car_price', 'vehicle_price', 'price_source', 'tenure_years',
        'down_payment_percent', 'finance_rate', 'insurance_rate', 'processing_fee',
        'down_payment_amount', 'bank_loan', 'first_year_insurance', 'monthly_installment',
        'total_initial_deposit', 'status', 'admin_notes', 'completed_at', 'canceled_at',
    ];

    protected $casts = [
        'model_year' => 'integer',
        'used_car_price' => 'integer',
        'vehicle_price' => 'integer',
        'tenure_years' => 'integer',
        'down_payment_percent' => 'decimal:2',
        'finance_rate' => 'decimal:4',
        'insurance_rate' => 'decimal:4',
        'processing_fee' => 'integer',
        'down_payment_amount' => 'integer',
        'bank_loan' => 'integer',
        'first_year_insurance' => 'integer',
        'monthly_installment' => 'integer',
        'total_initial_deposit' => 'integer',
        'completed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'cnic' => 'encrypted',
        'has_credit_cards_or_loans' => 'boolean',
    ];

    public function cnicMasked(): ?string
    {
        if (empty($this->cnic)) {
            return null;
        }

        return substr($this->cnic, 0, 6).'*******-'.substr($this->cnic, -1);
    }

    public static function statuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_CANCELED, self::STATUS_COMPLETED];
    }

    public function nextStatus(): ?string
    {
        return match ($this->status) {
            self::STATUS_PENDING => self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => self::STATUS_COMPLETED,
            default => null,
        };
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(CarFinanceBank::class, 'car_finance_bank_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }
}
