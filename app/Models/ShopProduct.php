<?php

namespace App\Models;

use App\Enums\ProductAvailabilityStatus;
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
        'availability_status',
        'stock_quantity',
        'category_id',
        'inlay',
        'description',
    ];

    protected $casts = [
        'zodiac_signs' => 'array',
        'availability_status' => ProductAvailabilityStatus::class,
        'stock_quantity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (ShopProduct $product): void {
            if (
                $product->availability_status === ProductAvailabilityStatus::InStock
                && $product->stock_quantity === 0
            ) {
                $product->availability_status = ProductAvailabilityStatus::OutOfStock;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ShopProductImage::class);
    }

    public function isPurchasable(int $quantity = 1): bool
    {
        return match ($this->availability_status) {
            ProductAvailabilityStatus::OutOfStock => false,
            ProductAvailabilityStatus::Preorder => true,
            ProductAvailabilityStatus::InStock => $this->stock_quantity === null || $this->stock_quantity >= $quantity,
        };
    }

    public function shouldDecrementStock(): bool
    {
        return $this->availability_status === ProductAvailabilityStatus::InStock
            && $this->stock_quantity !== null;
    }

    public function getStorefrontUrlAttribute(): ?string
    {
        $pattern = config('shop.product_url_pattern');

        if (! is_string($pattern) || trim($pattern) === '') {
            return null;
        }

        return strtr($pattern, [
            '{id}' => (string) $this->id,
            '{category_slug}' => (string) ($this->category?->slug ?? ''),
        ]);
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        /** @var ?ShopProductImage $image */
        $image = $this->images->first();

        return $image?->url;
    }

    /** @return string[] */
    public function getPreviewImageUrlsAttribute(): array
    {
        return $this->images
            ->map(fn (ShopProductImage $image) => $image->url)
            ->filter()
            ->values()
            ->all();
    }
}
