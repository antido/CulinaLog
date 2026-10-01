<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'name' => fake()->word(),
            'quantity' => fake()->numberBetween(1, 5),
            'unit' => fake()->randomElement(['cup', 'tbsp', 'g', null]),
            'sort_order' => 1,
        ];
    }
}
