<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id'       => null,
            'name'              => fake()->words(3, true),
            'short_description' => fake()->optional()->sentence(),
            'price'             => fake()->randomFloat(2, 500, 100000),
            'color'             => fake()->optional()->randomElement(['золотой', 'серебряный', 'розовый', 'белый']),
            'composition'       => fake()->optional()->randomElement(['серебро 925', 'золото 585', 'позолота', 'сталь']),
            'inlay'             => fake()->optional()->randomElement(['фианит', 'рубин', 'изумруд', 'бриллиант']),
            'lock_type'         => fake()->optional()->randomElement(['английский замок', 'карабин', 'пружина', 'шпингалет']),
            'length'            => fake()->optional()->randomElement(['40', '45', '50', '60']),
            'production'        => fake()->optional()->randomElement(['Россия', 'Италия', 'Китай']),
            'brand'             => fake()->optional()->randomElement(['SOKOLOV', 'Pandora', 'TODOS', 'Excalibur']),
            'zodiac_signs'      => [],
            'description'       => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Продукт принадлежит автоматически созданной категории.
     */
    public function withCategory(): static
    {
        return $this->state(['category_id' => ProductCategory::factory()]);
    }

    /**
     * Продукт с конкретными знаками зодиака.
     *
     * @param string[] $signs
     */
    public function withZodiacSigns(array $signs): static
    {
        return $this->state(['zodiac_signs' => $signs]);
    }
}
