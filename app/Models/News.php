<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'cover_image',
        'english_html',
        'urdu_html',
        'created_by',
        'updated_by',
    ];

    public function getCoverImageAttribute($image)
    {
        if (! empty($image) && ! filter_var($image, FILTER_VALIDATE_URL)) {
            return url(Storage::url($image));
        }

        return $image;
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeSearch($query, $search)
    {
        $search = '%' . $search . '%';

        return $query->where(function ($q) use ($search) {
            $q->where('news.id', 'LIKE', $search)
                ->orWhere('english_html', 'LIKE', $search)
                ->orWhere('urdu_html', 'LIKE', $search)
                ->orWhereHas('city', function ($cityQuery) use ($search) {
                    $cityQuery->where('cities.name', 'LIKE', $search);
                })
                ->orWhereHas('creator', function ($userQuery) use ($search) {
                    $userQuery->where('users.name', 'LIKE', $search);
                })
                ->orWhereHas('updater', function ($userQuery) use ($search) {
                    $userQuery->where('users.name', 'LIKE', $search);
                });
        });
    }

    public function scopeSort($query, $column, $order)
    {
        if ($column === 'city_name') {
            return $query->leftJoin('cities', 'cities.id', '=', 'news.city_id')
                ->orderBy('cities.name', $order)
                ->select('news.*');
        }

        if ($column === 'created_by_name') {
            return $query->leftJoin('users as creators', 'creators.id', '=', 'news.created_by')
                ->orderBy('creators.name', $order)
                ->select('news.*');
        }

        if ($column === 'updated_by_name') {
            return $query->leftJoin('users as updaters', 'updaters.id', '=', 'news.updated_by')
                ->orderBy('updaters.name', $order)
                ->select('news.*');
        }

        return $query->orderBy('news.' . $column, $order);
    }
}
