<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectBonus>
 */
class ProjectBonusFactory extends Factory
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
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'min_donation' => $this->faker->randomFloat(2, 50, 1000),
            'quantity' => $this->faker->optional(0.7)->numberBetween(5, 100),
            'quantity_claimed' => 0,
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Бонус з необмеженою кількістю
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => null,
        ]);
    }

    /**
     * Бонус з обмеженою кількістю
     */
    public function limited(int $quantity = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'quantity_claimed' => 0,
        ]);
    }

    /**
     * Повністю використаний бонус
     */
    public function exhausted(): static
    {
        $quantity = $this->faker->numberBetween(5, 20);

        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'quantity_claimed' => $quantity,
        ]);
    }
}
