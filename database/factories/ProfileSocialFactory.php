<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileSocial>
 */
class ProfileSocialFactory extends Factory
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
            'website' => fake()->url(),
            'facebook' => 'https://facebook.com/'.fake()->userName(),
            'twitter' => 'https://twitter.com/'.fake()->userName(),
            'instagram' => 'https://instagram.com/'.fake()->userName(),
            'linkedin' => 'https://linkedin.com/in/'.fake()->userName(),
            'youtube' => 'https://youtube.com/@'.fake()->userName(),
            'pinterest' => 'https://pinterest.com/'.fake()->userName(),
            'github' => 'https://github.com/'.fake()->userName(),
            'telegram' => 'https://t.me/'.fake()->userName(),
            'tiktok' => 'https://tiktok.com/@'.fake()->userName(),
            'youtube_channel' => 'https://youtube.com/channel/'.fake()->uuid(),
            'whatsapp' => 'https://wa.me/'.fake()->numerify('+380#########'),
            'deviantart' => 'https://deviantart.com/'.fake()->userName(),
        ];
    }
}
