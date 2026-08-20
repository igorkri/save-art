<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'slug' => Str::slug($name).'-'.Str::random(6),
            'name' => $name,
            'avatar' => 'teams/'.fake()->uuid().'.jpg',
            'website' => fake()->url(),
            'country' => 'Україна',
            'city' => fake()->city(),
            'region' => fake()->state(),
            'zip' => fake()->postcode(),
            'description' => fake()->paragraphs(2, true),
            'specialization' => fake()->jobTitle(),
            'social_links' => [],
        ];
    }
}
