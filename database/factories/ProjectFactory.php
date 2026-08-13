<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\ModerationStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Models\ArtCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $root = ArtCategory::whereNull('parent_id')->inRandomOrder()->first();
        $child = $root?->children()->inRandomOrder()->first();
        $artCategoryId = $child?->id ?? $root?->id;

        return [
            'user_id' => User::factory(),
            'user_type' => $this->faker->randomElement(UserType::cases()),
            'is_legal' => $this->faker->boolean(),
            'code' => strtoupper(Str::random(8)),
            'slug' => $this->faker->unique()->slug(),
            'status' => ProjectStatus::Draft,
            'status_moderation' => ModerationStatus::Pending,
            'title' => $this->faker->sentence(4),
            'short_description' => $this->faker->paragraph(),
            'cover' => null,
            'tags' => implode(', ', $this->faker->words(3)),
            'art_category_id' => $artCategoryId,
            'currency' => $this->faker->randomElement(Currency::cases()),
            'budget_goal' => $this->faker->randomFloat(2, 1000, 100000),
            'budget_collected' => 0,
            'estimated_days' => $this->faker->numberBetween(30, 365),
            'budget_items' => null,
            'additional_info' => null,
            'content_blocks' => null,
            'final_result' => null,
            'likes_count' => 0,
            'donors_count' => 0,
            'announced_at' => null,
            'planned_completion_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Оголошений проєкт
     */
    public function announced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Announced,
            'status_moderation' => ModerationStatus::Approved,
            'announced_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Проєкт у роботі
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::InProgress,
            'status_moderation' => ModerationStatus::Approved,
            'announced_at' => $this->faker->dateTimeBetween('-3 months', '-1 month'),
            'budget_collected' => $this->faker->randomFloat(2, 100, $attributes['budget_goal'] * 0.8),
        ]);
    }

    /**
     * Завершений проєкт
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Completed,
            'status_moderation' => ModerationStatus::Approved,
            'announced_at' => $this->faker->dateTimeBetween('-6 months', '-3 months'),
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'budget_collected' => $attributes['budget_goal'],
        ]);
    }

    /**
     * Проєкт на модерації
     */
    public function moderation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Moderation,
            'status_moderation' => ModerationStatus::Pending,
        ]);
    }

    /**
     * Відхилений проєкт
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Rejected,
            'status_moderation' => ModerationStatus::Rejected,
        ]);
    }

    /**
     * Проєкт з конкретним автором
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Проєкт з прикладами контент-блоків
     */
    public function withContentBlocks(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_blocks' => [
                [
                    'type' => 'heading',
                    'heading_level' => 'h2',
                    'heading_text' => 'Про проєкт',
                ],
                [
                    'type' => 'paragraph',
                    'paragraph_text' => $this->faker->paragraphs(3, true),
                ],
                [
                    'type' => 'heading',
                    'heading_level' => 'h3',
                    'heading_text' => 'Чому це важливо',
                ],
                [
                    'type' => 'paragraph',
                    'paragraph_text' => $this->faker->paragraphs(2, true),
                ],
            ],
        ]);
    }
}
