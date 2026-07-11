<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'compare_at_price' => $this->faker->randomFloat(2, 500, 1000),
            'stock' => $this->faker->numberBetween(1, 100),
            'category_id' => \App\Models\Category::factory(),
            'image' => null,
            'sizes' => json_encode(['XS', 'S', 'M', 'L', 'XL']),
            'sizes_stock' => json_encode(['XS' => 10, 'S' => 15, 'M' => 20, 'L' => 15, 'XL' => 10]),
            'color_options' => json_encode(['Black', 'White', 'Red']),
            'colors_stock' => json_encode(['Black' => 20, 'White' => 15, 'Red' => 10]),
        ];
    }

    public function outOfStock()
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
            'sizes_stock' => json_encode(['XS' => 0, 'S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0]),
            'colors_stock' => json_encode(['Black' => 0, 'White' => 0, 'Red' => 0]),
        ]);
    }

    public function onSale()
    {
        return $this->state(fn (array $attributes) => [
            'compare_at_price' => 100,
            'price' => 49.99,
        ]);
    }
}
