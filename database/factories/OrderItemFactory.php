<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 5);
        $unitPrice = fake()->randomFloat(2, 2, 25);
        $taxRate = fake()->randomElement([0, 5, 8, 10]);
        $discount = fake()->randomFloat(2, 0, 2);
        $lineSubtotal = $quantity * $unitPrice;
        $taxableAmount = max($lineSubtotal - $discount, 0);
        $taxAmount = round($taxableAmount * ($taxRate / 100), 2);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount,
            'line_total' => round($taxableAmount + $taxAmount, 2),
            'meta' => ['unit' => 'pcs'],
        ];
    }
}
