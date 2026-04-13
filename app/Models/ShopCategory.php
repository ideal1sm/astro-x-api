<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShopCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'show_on_home',
        'show_in_catalog',
    ];

    protected $casts = [
        'show_on_home'    => 'boolean',
        'show_in_catalog' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class, 'category_id');
    }
}
