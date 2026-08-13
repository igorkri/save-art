<?php

namespace Database\Factories;

use App\Enums\StageStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectStage>
 */
class ProjectStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'order' => $this->faker->numberBetween(0, 5),
            'status' => StageStatus::Planned,
            'title' => $this->faker->sentence(2),
            'description' => $this->faker->paragraph(),
            'budget_planned' => $this->faker->randomFloat(2, 1000, 10000),
            'budget_actual' => null,
            'days_planned' => $this->faker->numberBetween(7, 60),
            'started_at' => null,
            'completed_at' => null,
            'documents' => null,
        ];
    }

    /**
     * Етап в процесі виконання
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StageStatus::InProgress,
            'started_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Завершений етап
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StageStatus::Completed,
            'started_at' => $this->faker->dateTimeBetween('-3 months', '-1 month'),
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'budget_actual' => $this->faker->randomFloat(2, $attributes['budget_planned'] * 0.8, $attributes['budget_planned'] * 1.2),
        ]);
    }
}
