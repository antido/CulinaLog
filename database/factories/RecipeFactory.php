<?php

namespace Database\Factories;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state: a published, visible recipe.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => Str::title(fake()->unique()->words(3, true)),
            'description' => fake()->paragraph(),
            'prep_time' => fake()->numberBetween(5, 30),
            'cook_time' => fake()->numberBetween(0, 90),
            'servings' => fake()->numberBetween(1, 8),
            'difficulty' => fake()->randomElement(RecipeDifficulty::cases()),
            'status' => RecipeStatus::Published,
            'published_at' => now(),
        ];
    }

    /**
     * Fill in the fields that are not mass assignable.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Recipe $recipe): void {
            $recipe->slug ??= Recipe::uniqueSlug($recipe->title);
        });
    }

    /**
     * Indicate that the recipe is an unpublished draft.
     */
    public function draft(): static
    {
        return $this->state(fn () => ['status' => RecipeStatus::Draft, 'published_at' => null]);
    }

    /**
     * Indicate that the author archived the recipe.
     */
    public function archived(): static
    {
        return $this->state(fn () => ['status' => RecipeStatus::Archived]);
    }

    /**
     * Indicate that a moderator hid the recipe.
     */
    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
