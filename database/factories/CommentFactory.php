<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state: a visible comment by a member.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'status' => CommentStatus::Visible,
            'depth' => 0,
        ];
    }

    /**
     * Indicate that a guest wrote the comment.
     */
    public function guest(?string $name = null): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'guest_name' => $name,
            'guest_token_hash' => hash('sha256', fake()->unique()->uuid()),
        ]);
    }

    /**
     * Indicate that the comment is waiting for approval.
     */
    public function pending(): static
    {
        return $this->state(fn () => ['status' => CommentStatus::Pending]);
    }

    /**
     * Indicate that a moderator hid the comment.
     */
    public function hidden(): static
    {
        return $this->state(fn () => ['status' => CommentStatus::Hidden]);
    }

    /**
     * Indicate that the comment replies to another comment.
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn () => [
            'recipe_id' => $parent->recipe_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }
}
