<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArtCatalog>
 */
class ArtCatalogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'title' => ['uk' => $title, 'en' => $title],
            'image' => 'catalogs/'.fake()->uuid().'.jpg',
            'published_at' => fake()->date(),
            'likes_count' => fake()->numberBetween(0, 100),
            'pdf_file' => fake()->uuid().'.pdf',
        ];
    }
}
