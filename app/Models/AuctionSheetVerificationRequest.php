<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionSheetVerificationRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    public const NOTIFICATION_PENDING = 'pending';
    public const NOTIFICATION_SENT = 'sent';
    public const NOTIFICATION_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'chassis_number',
        'phone_number',
        'phone_number_normalized',
        'status',
        'report_url',
        'admin_notes',
        'notification_status',
        'notified_at',
        'completed_at',
        'price_amount',
        'currency_code',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'completed_at' => 'datetime',
        'price_amount' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [self::STATUS_PENDING, self::STATUS_COMPLETED];
    }

    public static function normalizeChassisNumber(string $value): string
    {
        $value = str_replace(['‐', '‑', '‒', '–', '—', '﹘', '﹣', '－'], '-', trim($value));

        return preg_replace('/\s+/u', '', mb_strtoupper($value)) ?? '';
    }

    public static function normalizePhoneNumber(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
