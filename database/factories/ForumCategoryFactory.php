<?php

namespace Database\Factories;

use App\Models\ForumCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumCategory>
 */
class ForumCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::title(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Fill in the slug, which is not mass assignable.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ForumCategory $category): void {
            $category->slug ??= ForumCategory::uniqueSlug($category->name);
        });
    }

    /**
     * Indicate that the category is hidden from visitors.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
