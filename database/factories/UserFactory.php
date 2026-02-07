<?php

namespace Database\Factories;

use App\Enums\ProfileType;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'full_name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.Str::random(6),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::User->value,
            'profile_type' => fake()->randomElement([ProfileType::Artist->value, ProfileType::Patron->value]),
        ];
    }

    /**
     * Створити користувача з повними профілями.
     */
    public function withProfiles(): static
    {
        return $this->state(function (array $attributes) {
            $fullName = fake()->name();

            return [
                'avatar' => 'avatars/'.fake()->uuid().'.jpg',
                'full_name' => ['uk' => $fullName, 'en' => $fullName],
                'profession' => ['uk' => fake()->jobTitle(), 'en' => fake()->jobTitle()],
                'tags' => ['uk' => fake()->words(3, true), 'en' => fake()->words(3, true)],
                'country' => ['uk' => 'Україна', 'en' => 'Ukraine'],
                'region' => ['uk' => fake()->city().' область', 'en' => fake()->city().' region'],
                'city' => ['uk' => fake()->city(), 'en' => fake()->city()],
                'postal_code' => fake()->postcode(),
                'description' => ['uk' => fake()->paragraph(), 'en' => fake()->paragraph()],
            ];
        })->afterCreating(function ($user) {
            \App\Models\ProfileLegal::factory()->create(['user_id' => $user->id]);
            \App\Models\ProfileSocial::factory()->create(['user_id' => $user->id]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Створити адміністратора.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin->value,
        ]);
    }

    /**
     * Створити митця.
     */
    public function artist(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_type' => ProfileType::Artist->value,
        ]);
    }

    /**
     * Створити мецената.
     */
    public function patron(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_type' => ProfileType::Patron->value,
        ]);
    }
}
