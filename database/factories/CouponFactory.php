<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('TEST-????')),
            'discount_type' => $this->faker->randomElement(['percentage', 'fixed']),
            'discount_value' => $this->faker->randomElement([10, 20, 50]),
            'min_order_amount' => $this->faker->randomElement([0, 50, 100]),
            'max_uses' => $this->faker->numberBetween(5, 100),
            'used_count' => 0,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ];
    }

    public function percentageDiscount($percent = 10)
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percentage',
            'discount_value' => $percent,
        ]);
    }

    public function fixedDiscount($amount = 50)
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'fixed',
            'discount_value' => $amount,
        ]);
    }

    public function expired()
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
            'is_active' => false,
        ]);
    }
}
