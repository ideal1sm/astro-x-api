<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
            'user_id'                  => User::factory(),
            'status'                   => OrderStatus::Created,
            'payment_method'           => PaymentMethod::Yookassa,
            'payment_status'           => PaymentStatus::Pending,
            'yookassa_payment_id'      => 'pay_' . fake()->unique()->numerify('########'),
            'items_total'              => '4200.00',
            'delivery_method'          => 'pickup_point',
            'delivery_price'           => '350.00',
            'delivery_payload'         => [
                'code' => 'PVZ-001',
                'label' => 'ПВЗ на Невском',
            ],
            'recipient_name'           => fake()->name(),
            'recipient_phone'          => '+7 999 555-44-33',
            'delivery_city'            => 'Санкт-Петербург',
            'delivery_address'         => null,
            'delivery_pickup_point'    => 'ПВЗ на Невском, Невский проспект, 1',
            'delivery_comment'         => fake()->optional()->sentence(),
            'total'                    => '4550.00',
            'payment_amount'           => '4550.00',
            'payment_confirmation_url' => 'https://pay.example.test/confirm',
            'payment_initiated_at'     => now(),
            'payment_paid_at'          => null,
            'payment_failure_reason'   => null,
            'payment_payload'          => [
                'id' => 'pay_' . fake()->unique()->numerify('########'),
                'status' => 'pending',
            ],
            'customer_name'            => fake()->name(),
            'customer_phone'           => '+7 999 123-45-67',
            'customer_email'           => fake()->safeEmail(),
            'personal_data_consent_at' => now(),
            'delivery_address_id'      => null,
            'notes'                    => fake()->optional()->sentence(),
        ];
    }
}
