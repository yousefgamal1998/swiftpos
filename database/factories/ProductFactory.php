<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'barcode' => fake()->optional()->ean13(),
            'name' => str($name)->title()->toString(),
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['retail', 'restaurant']),
            'price' => fake()->randomFloat(2, 1, 40),
            'cost' => fake()->randomFloat(2, 0.5, 25),
            'tax_rate' => fake()->randomElement([0, 5, 8, 10]),
            'track_inventory' => true,
            'stock_quantity' => fake()->randomFloat(3, 10, 250),
            'low_stock_threshold' => 10,
            'unit' => 'pcs',
            'is_active' => true,
            'meta' => null,
        ];
    }
}
