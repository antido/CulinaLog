<?php

namespace Database\Factories;

use App\Enums\ForumStatus;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumTopic>
 */
class ForumTopicFactory extends Factory
{
    /**
     * Define the model's default state: a visible, open topic.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => ForumCategory::factory(),
            'title' => Str::title(fake()->unique()->words(4, true)),
            'body' => fake()->paragraph(),
            'status' => ForumStatus::Visible,
            'last_activity_at' => now(),
        ];
    }

    /**
     * Fill in the slug, which is not mass assignable.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ForumTopic $topic): void {
            $topic->slug ??= ForumTopic::uniqueSlug($topic->title);
        });
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => ForumStatus::Hidden]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    public function locked(): static
    {
        return $this->state(fn () => ['is_locked' => true]);
    }
}
