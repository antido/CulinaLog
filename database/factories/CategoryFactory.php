<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Fill in the slug, which is not mass assignable.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Category $category): void {
            $category->slug ??= Category::uniqueSlug($category->name);
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
