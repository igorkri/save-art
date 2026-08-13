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
            'is_active' => true,
            'currency' => fake()->randomElement(['UAH', 'USD', 'EUR']),
            'logo' => fake()->imageUrl(400, 400, 'business'),
            'name' => fake()->company(),
            'edrpou' => fake()->numerify('########'),
            'authorized_person' => fake()->name(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
        ];
    }
}
