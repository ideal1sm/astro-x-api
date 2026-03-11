<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        // Slug генерируется автоматически в ProductCategory::booted() из name,
        // поэтому здесь не задаём его явно — используем уникальный name.
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
