<?php

namespace Database\Factories;

use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopOrderItem>
 */
class ShopOrderItemFactory extends Factory
{
    protected $model = ShopOrderItem::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 300, 10000);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'shop_order_id'   => ShopOrder::factory(),
            'shop_product_id' => ShopProduct::factory(),
            'quantity'        => $quantity,
            'price'           => $price,
            'total'           => round($price * $quantity, 2),
        ];
    }
}
