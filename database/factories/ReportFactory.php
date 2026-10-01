<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Recipe;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state: a pending report about a recipe.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reportable_type' => 'recipe',
            'reportable_id' => Recipe::factory(),
            'reason' => ReportReason::Spam,
            'description' => fake()->sentence(),
            'status' => ReportStatus::Pending,
        ];
    }

    /**
     * Indicate what the report is about.
     */
    public function about(Model $content): static
    {
        return $this->state(fn () => [
            'reportable_type' => $content->getMorphClass(),
            'reportable_id' => $content->getKey(),
        ]);
    }

    /**
     * Indicate that a moderator already resolved the report.
     */
    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => ReportStatus::Resolved,
            'reviewed_by' => User::factory()->moderator(),
            'reviewed_at' => now(),
        ]);
    }
}
