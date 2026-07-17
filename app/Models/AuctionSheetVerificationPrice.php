<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionSheetVerificationPrice extends Model
{
    use HasFactory;

    public const SINGLETON_ID = 1;

    protected $fillable = ['price_amount', 'currency_code', 'updated_by'];

    protected $casts = ['price_amount' => 'decimal:2'];

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => self::SINGLETON_ID],
            ['price_amount' => 2950, 'currency_code' => 'PKR']
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
