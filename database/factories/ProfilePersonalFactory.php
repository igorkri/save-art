<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfilePersonal>
 */
class ProfilePersonalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
            return [
                'user_id' => null, // будет установлен при создании через связь
                'avatar' => fake()->imageUrl(400, 400, 'people'),
                'full_name' => [
                    'uk' => fake()->name(),
                    'en' => fake()->name(),
                ],
                'profession' => [
                    'uk' => fake()->jobTitle(),
                    'en' => fake()->jobTitle(),
                ],
                'tags' => [
                    'uk' => implode(', ', fake()->words(3)),
                    'en' => implode(', ', fake()->words(3)),
                ],
                'country' => [
                    'uk' => 'Україна',
                    'en' => 'Ukraine',
                ],
                'region' => [
                    'uk' => fake()->state(),
                    'en' => fake()->state(),
                ],
                'city' => [
                    'uk' => fake()->city(),
                    'en' => fake()->city(),
                ],
                'postal_code' => fake()->postcode(),
                'role' => null,
                'description' => [
                    'uk' => fake()->realText(100),
                    'en' => fake()->realText(100),
                ],
            ];
    }
}
