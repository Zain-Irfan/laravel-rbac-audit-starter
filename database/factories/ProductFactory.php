<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 15, 400);

        return [
            'name' => ucfirst(fake()->unique()->words(fake()->numberBetween(2, 3), true)),
            'sku' => strtoupper(fake()->unique()->bothify('??-####')),
            'price' => $price,
            'cost' => round($price * fake()->randomFloat(2, 0.35, 0.75), 2),
            'stock' => fake()->numberBetween(0, 250),
        ];
    }
}
