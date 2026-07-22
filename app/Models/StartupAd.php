<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StartupAd extends Model
{
    use HasFactory;

    public const TYPE_INSPECTION = 'inspection';

    protected $fillable = [
        'name',
        'image',
        'url',
        'type',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageAttribute(?string $image): ?string
    {
        if (! empty($image) && ! filter_var($image, FILTER_VALIDATE_URL)) {
            return url(Storage::url($image));
        }

        return $image;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}
