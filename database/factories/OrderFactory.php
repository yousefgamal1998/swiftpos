<?php

namespace Database\Factories;

use App\Models\PosSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5, 100);
        $discount = fake()->randomFloat(2, 0, 10);
        $tax = fake()->randomFloat(2, 0, 12);
        $total = max($subtotal - $discount + $tax, 0);

        return [
            'order_number' => sprintf('SP-%s-%s', now()->format('YmdHis'), strtoupper(fake()->lexify('????'))),
            'user_id' => User::factory(),
            'pos_session_id' => PosSession::factory(),
            'status' => 'completed',
            'order_type' => fake()->randomElement(['retail', 'dine_in', 'takeaway', 'delivery']),
            'customer_name' => fake()->optional()->name(),
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'paid_amount' => $total,
            'change_amount' => 0,
            'payment_status' => 'paid',
            'notes' => fake()->optional()->sentence(),
            'placed_at' => now(),
        ];
    }
}
