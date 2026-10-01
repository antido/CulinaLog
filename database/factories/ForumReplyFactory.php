<?php

namespace Database\Factories;

use App\Enums\ForumStatus;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumReply>
 */
class ForumReplyFactory extends Factory
{
    /**
     * Define the model's default state: a visible reply by a member.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => ForumTopic::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'status' => ForumStatus::Visible,
            'depth' => 0,
        ];
    }

    /**
     * Indicate that a guest wrote the reply.
     */
    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'guest_token_hash' => hash('sha256', fake()->unique()->uuid()),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => ForumStatus::Pending]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => ForumStatus::Hidden]);
    }

    /**
     * Indicate that the reply answers another reply.
     */
    public function replyTo(ForumReply $parent): static
    {
        return $this->state(fn () => [
            'topic_id' => $parent->topic_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }
}
