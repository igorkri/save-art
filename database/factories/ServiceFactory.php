<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
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
            'serviceable_type' => User::class,
            'serviceable_id' => User::factory(),
            'title' => ['uk' => $title, 'en' => $title],
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => ['uk' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'location' => ['uk' => fake()->city(), 'en' => fake()->city()],
            'price' => fake()->randomFloat(2, 500, 20000),
            'currency' => fake()->randomElement(Currency::cases())->value,
        ];
    }
}
