<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->words(3, true),
            'category_id' => Category::factory(),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->randomFloat(2, 1, 999),
            'stock'       => $this->faker->numberBetween(0, 999),
            'enabled'     => $this->faker->boolean(),
        ];
    }
}
