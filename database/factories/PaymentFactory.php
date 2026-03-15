<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'method' => fake()->randomElement(['cash', 'card', 'wallet', 'bank_transfer']),
            'amount' => fake()->randomFloat(2, 1, 120),
            'status' => 'completed',
            'reference' => fake()->optional()->uuid(),
            'paid_at' => now(),
            'notes' => null,
        ];
    }
}
