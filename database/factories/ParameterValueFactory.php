<?php

namespace Database\Factories;

use App\Models\Parameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParameterValue>
 */
class ParameterValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parameter_id' => Parameter::factory(),
            'value' => [
                'uk' => $this->faker->word(),
                'en' => $this->faker->word(),
            ],
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
