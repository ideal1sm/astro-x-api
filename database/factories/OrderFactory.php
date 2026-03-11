<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'status'              => OrderStatus::Created,
            'total'               => fake()->randomFloat(2, 500, 100000),
            'delivery_address_id' => null,
            'notes'               => fake()->optional()->sentence(),
        ];
    }

    public function withStatus(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
