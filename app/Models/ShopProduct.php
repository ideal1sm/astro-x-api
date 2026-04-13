<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_description',
        'color',
        'composition',
        'price',
        'lock_type',
        'length',
        'production',
        'brand',
        'zodiac_signs',
        'category_id',
        'inlay',
        'description',
    ];

    protected $casts = [
        'zodiac_signs' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ShopProductImage::class);
    }
}
