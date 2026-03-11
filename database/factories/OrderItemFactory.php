<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $price    = fake()->randomFloat(2, 500, 50000);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'order_id'   => Order::factory(),
            'product_id' => Product::factory(),
            'quantity'   => $quantity,
            'price'      => $price,
            'total'      => round($price * $quantity, 2),
        ];
    }
}
