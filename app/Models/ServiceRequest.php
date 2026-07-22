<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class ServiceRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_COMPLETED = 'completed';

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
        'visit_area',
        'visit_date',
        'visit_start_time',
        'visit_end_time',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'model_year' => 'integer',
        'visit_date' => 'date:Y-m-d',
    ];

    abstract public function serviceType(): string;

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

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
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
