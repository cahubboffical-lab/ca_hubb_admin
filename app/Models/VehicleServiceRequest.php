<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class VehicleServiceRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'full_name',
        'phone_number',
        'phone_number_normalized',
        'is_filer',
        'car_model_id',
        'model_year',
        'car_variant',
        'registration_place',
        'status',
        'admin_notes',
        'completed_at',
    ];

    protected $casts = [
        'is_filer' => 'boolean',
        'model_year' => 'integer',
        'completed_at' => 'datetime',
    ];

    abstract public function serviceLabel(): string;

    public static function statuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_CANCELED, self::STATUS_COMPLETED];
    }

    public static function normalizePhoneNumber(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
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

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }
}
