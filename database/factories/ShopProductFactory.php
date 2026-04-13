<?php

namespace Database\Factories;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProduct>
 */
class ShopProductFactory extends Factory
{
    protected $model = ShopProduct::class;

    public function definition(): array
    {
        return [
            'category_id'       => null,
            'name'              => fake()->words(3, true),
            'short_description' => fake()->optional()->sentence(),
            'price'             => fake()->randomFloat(2, 300, 10000),
            'color'             => fake()->optional()->randomElement(['янтарный', 'золотистый', 'темный', 'светлый']),
            'brand'             => fake()->optional()->randomElement(['Медовый дом', 'Пасека', 'Таежный сбор']),
            'composition'       => fake()->optional()->randomElement(['мед натуральный', 'мед с прополисом', 'мед с орехами']),
            'inlay'             => fake()->optional()->randomElement(['орехи', 'прополис', 'ягоды', 'травы']),
            'lock_type'         => fake()->optional()->randomElement(['банка', 'крышка твист-офф', 'подарочная упаковка']),
            'length'            => fake()->optional()->randomElement(['250 г', '500 г', '1 кг']),
            'production'        => fake()->optional()->randomElement(['Россия', 'Алтай', 'Башкортостан']),
            'zodiac_signs'      => [],
            'description'       => fake()->optional()->sentence(),
        ];
    }

    public function withCategory(): static
    {
        return $this->state(['category_id' => ShopCategory::factory()]);
    }
}
