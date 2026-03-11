<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'title'       => fake()->optional()->randomElement(['Домашний', 'Рабочий']),
            'country'     => 'Россия',
            'city'        => fake()->city(),
            'street'      => fake()->streetAddress(),
            'apartment'   => fake()->optional()->numerify('##'),
            'postal_code' => fake()->numerify('######'),
            'is_default'  => false,
        ];
    }
}
