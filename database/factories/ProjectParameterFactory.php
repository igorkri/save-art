<?php

namespace Database\Factories;

use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectParameter>
 */
class ProjectParameterFactory extends Factory
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
            'parameter_id' => Parameter::factory(),
            'parameter_value_id' => ParameterValue::factory(),
            'custom_value' => null,
        ];
    }

    /**
     * Довільне значення характеристики (без прив'язки до ParameterValue)
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'parameter_value_id' => null,
            'custom_value' => [
                'uk' => $this->faker->word(),
                'en' => $this->faker->word(),
            ],
        ]);
    }
}
