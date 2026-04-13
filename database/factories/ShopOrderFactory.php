<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopOrder>
 */
class ShopOrderFactory extends Factory
{
    protected $model = ShopOrder::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'status'              => OrderStatus::Created,
            'total'               => fake()->randomFloat(2, 300, 50000),
            'delivery_address_id' => null,
            'notes'               => fake()->optional()->sentence(),
        ];
    }
}
