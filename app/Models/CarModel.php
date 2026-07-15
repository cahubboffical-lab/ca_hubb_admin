<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand_name',
        'price',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

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
        $search = '%'.$search.'%';

        return $query->where(function ($query) use ($search) {
            $query->where('car_models.id', 'LIKE', $search)
                ->orWhere('car_models.name', 'LIKE', $search)
                ->orWhere('car_models.brand_name', 'LIKE', $search)
                ->orWhere('car_models.price', 'LIKE', $search)
                ->orWhereHas('creator', fn ($userQuery) => $userQuery->where('users.name', 'LIKE', $search))
                ->orWhereHas('updater', fn ($userQuery) => $userQuery->where('users.name', 'LIKE', $search));
        });
    }

    public function scopeSort($query, string $column, string $order)
    {
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        if ($column === 'created_by_name') {
            return $query->leftJoin('users as creators', 'creators.id', '=', 'car_models.created_by')
                ->orderBy('creators.name', $order)
                ->select('car_models.*');
        }

        if ($column === 'updated_by_name') {
            return $query->leftJoin('users as updaters', 'updaters.id', '=', 'car_models.updated_by')
                ->orderBy('updaters.name', $order)
                ->select('car_models.*');
        }

        $column = in_array($column, ['id', 'name', 'brand_name', 'price', 'created_at', 'updated_at'], true)
            ? $column
            : 'id';

        return $query->orderBy('car_models.'.$column, $order);
    }
}
