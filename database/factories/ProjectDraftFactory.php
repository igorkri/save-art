<?php

namespace Database\Factories;

use App\Models\ProjectDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectDraft>
 */
class ProjectDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => null,
            'status' => ProjectDraft::STATUS_NEW,
            'data' => [
                'title' => ['uk' => fake()->sentence(3), 'en' => fake()->sentence(3)],
                'short_description' => ['uk' => fake()->paragraph(), 'en' => fake()->paragraph()],
                'budget_goal' => fake()->numberBetween(1000, 100000),
                'currency' => fake()->randomElement(['UAH', 'USD', 'EUR']),
            ],
        ];
    }

    /**
     * Статус "exported"
     */
    public function exported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectDraft::STATUS_EXPORTED,
        ]);
    }

    /**
     * Статус "archived"
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectDraft::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Статус "deleted"
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectDraft::STATUS_DELETED,
        ]);
    }
}
