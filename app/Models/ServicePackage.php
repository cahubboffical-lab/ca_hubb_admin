<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServicePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'features',
        'icon',
        'price',
        'type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
    ];

    protected $appends = ['type_label'];

    public function getIconAttribute($icon)
    {
        if (! empty($icon) && ! filter_var($icon, FILTER_VALIDATE_URL)) {
            return url(Storage::url($icon));
        }

        return $icon;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'car_inspection' => __('Car Inspection'),
            'sell_for_me' => __('Sell for Me'),
            default => Str::of((string) $this->type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeSearch($query, string $search)
    {
        $search = '%' . $search . '%';

        return $query->where(function ($builder) use ($search) {
            $builder->where('service_packages.id', 'LIKE', $search)
                ->orWhere('service_packages.name', 'LIKE', $search)
                ->orWhere('service_packages.price', 'LIKE', $search)
                ->orWhere('service_packages.type', 'LIKE', $search)
                ->orWhere('service_packages.features', 'LIKE', $search)
                ->orWhere('service_packages.created_at', 'LIKE', $search)
                ->orWhere('service_packages.updated_at', 'LIKE', $search)
                ->orWhereHas('creator', static function ($creatorQuery) use ($search) {
                    $creatorQuery->where('users.name', 'LIKE', $search);
                })
                ->orWhereHas('updater', static function ($updaterQuery) use ($search) {
                    $updaterQuery->where('users.name', 'LIKE', $search);
                });
        });
    }

    public function scopeSort($query, string $column, string $order = 'DESC')
    {
        if ($column === 'created_by_name') {
            return $query->leftJoin('users as creators', 'creators.id', '=', 'service_packages.created_by')
                ->orderBy('creators.name', $order)
                ->select('service_packages.*');
        }

        if ($column === 'updated_by_name') {
            return $query->leftJoin('users as updaters', 'updaters.id', '=', 'service_packages.updated_by')
                ->orderBy('updaters.name', $order)
                ->select('service_packages.*');
        }

        return $query->orderBy('service_packages.' . $column, $order);
    }
}
