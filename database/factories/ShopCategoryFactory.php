<?php

namespace Database\Factories;

use App\Models\ShopCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopCategory>
 */
class ShopCategoryFactory extends Factory
{
    protected $model = ShopCategory::class;

    public function definition(): array
    {
        return [
            'name'            => fake()->unique()->words(2, true),
            'description'     => fake()->optional()->sentence(),
            'show_on_home'    => false,
            'show_in_catalog' => false,
        ];
    }

    public function onHome(): static
    {
        return $this->state(['show_on_home' => true]);
    }

    public function inCatalog(): static
    {
        return $this->state(['show_in_catalog' => true]);
    }
}
