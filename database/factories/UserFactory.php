<?php

namespace Database\Factories;

use App\Enums\ProfileType;
use App\Models\ProfileLegal;
use App\Models\ProfileSocial;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'full_name' => $name,
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
                'full_name' => $fullName,
                'profession' => fake()->jobTitle(),
                'tags' => fake()->words(3, true),
                'country' => 'Україна',
                'region' => fake()->city().' область',
                'city' => fake()->city(),
                'postal_code' => fake()->postcode(),
                'description' => fake()->paragraph(),
            ];
        })->afterCreating(function ($user) {
            ProfileLegal::factory()->create(['user_id' => $user->id]);
            ProfileSocial::factory()->create(['user_id' => $user->id]);
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

    /**
     * Позначити, що користувач вже зберіг обов'язкові поля профілю в кабінеті
     * (див. User::isProfileComplete()) — без цього EnsureFilamentProfileIsComplete
     * редиректить будь-яку сторінку панелі "profile", крім самого редагування
     * профілю, на форму редагування.
     */
    public function profileCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_completed_at' => now(),
        ]);
    }
}
