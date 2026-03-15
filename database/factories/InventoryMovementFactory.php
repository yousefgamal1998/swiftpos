<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previous = fake()->randomFloat(3, 5, 100);
        $delta = fake()->randomFloat(3, -10, 20);
        $newStock = max($previous + $delta, 0);

        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'movement_type' => fake()->randomElement(['sale', 'restock', 'purchase', 'adjustment', 'return']),
            'quantity' => $delta,
            'previous_stock' => $previous,
            'new_stock' => $newStock,
            'note' => fake()->optional()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
        ];
    }
}
