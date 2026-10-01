<?php

namespace Database\Factories;

use App\Enums\ReactionType;
use App\Models\Reaction;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
{
    /**
     * Define the model's default state: a "like" from a member.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'user_id' => User::factory(),
            'type' => ReactionType::Like,
        ];
    }

    /**
     * Indicate that a guest reacted.
     */
    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'guest_token_hash' => hash('sha256', fake()->unique()->uuid()),
        ]);
    }
}
