<?php

namespace Database\Factories;

use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProductImage>
 */
class ShopProductImageFactory extends Factory
{
    protected $model = ShopProductImage::class;

    public function definition(): array
    {
        return [
            'shop_product_id' => ShopProduct::factory(),
            'path'            => 'shop/products/' . fake()->uuid() . '.jpg',
        ];
    }
}
