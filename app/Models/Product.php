<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'color',
        'composition',
        'price',
        'inlay',
        'lock_type',
        'length',
        'production',
        'brand',
        'zodiac_sign',
    ];


    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
}
