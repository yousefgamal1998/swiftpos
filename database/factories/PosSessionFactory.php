<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PosSession>
 */
class PosSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $openedAt = fake()->dateTimeBetween('-1 week', 'now');

        return [
            'user_id' => User::factory(),
            'opened_at' => $openedAt,
            'closed_at' => null,
            'opening_cash' => fake()->randomFloat(2, 20, 200),
            'closing_cash' => null,
            'expected_cash' => null,
            'status' => 'open',
            'notes' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes): array {
            $openingCash = (float) ($attributes['opening_cash'] ?? fake()->randomFloat(2, 20, 200));
            $closingCash = fake()->randomFloat(2, max($openingCash - 20, 0), $openingCash + 50);

            return [
                'status' => 'closed',
                'closed_at' => now(),
                'closing_cash' => $closingCash,
                'expected_cash' => $openingCash,
            ];
        });
    }
}
