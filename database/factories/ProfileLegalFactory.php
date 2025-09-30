<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileLegal>
 */
class ProfileLegalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
            return [
                'user_id' => null,
                'currency' => fake()->randomElement(['UAH', 'USD', 'EUR']),
                'is_legal' => fake()->boolean(),
                'logo' => fake()->imageUrl(400, 400, 'business'),
                'name' => [
                    'uk' => fake()->company(),
                    'en' => fake()->company(),
                ],
                'edrpou' => fake()->numerify('########'),
                'authorized_person' => [
                    'uk' => fake()->name(),
                    'en' => fake()->name(),
                ],
                'address' => [
                    'uk' => fake()->address(),
                    'en' => fake()->address(),
                ],
                'phone' => fake()->phoneNumber(),
                'email' => fake()->companyEmail(),
            ];
    }
}
