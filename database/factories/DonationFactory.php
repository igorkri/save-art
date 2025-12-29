<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\Project;
use App\Models\ProjectBonus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isAnonymous = $this->faker->boolean(20);

        return [
            'project_id' => Project::factory(),
            'user_id' => $isAnonymous ? null : User::factory(),
            'project_bonus_id' => null,
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'currency' => $this->faker->randomElement(Currency::cases()),
            'amount_usd' => $this->faker->randomFloat(2, 5, 2000),
            'donor_type' => $this->faker->randomElement(['personal', 'legal']),
            'is_anonymous' => $isAnonymous,
            'donor_name' => $isAnonymous ? $this->faker->name() : null,
            'donor_email' => $isAnonymous ? $this->faker->email() : null,
            'status' => 'pending',
            'payment_method' => null,
            'payment_id' => null,
            'paid_at' => null,
        ];
    }

    /**
     * Оплачений донат
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'payment_method' => $this->faker->randomElement(['card', 'bank_transfer', 'paypal']),
            'payment_id' => $this->faker->uuid(),
            'paid_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Невдалий донат
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Повернений донат
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'payment_method' => $this->faker->randomElement(['card', 'bank_transfer']),
            'payment_id' => $this->faker->uuid(),
            'paid_at' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
        ]);
    }

    /**
     * Анонімний донат
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'is_anonymous' => true,
            'donor_name' => $this->faker->name(),
            'donor_email' => $this->faker->email(),
        ]);
    }

    /**
     * Донат від авторизованого користувача
     */
    public function fromUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
            'is_anonymous' => false,
            'donor_name' => null,
            'donor_email' => null,
        ]);
    }

    /**
     * Донат з бонусом
     */
    public function withBonus(?ProjectBonus $bonus = null): static
    {
        return $this->state(fn (array $attributes) => [
            'project_bonus_id' => $bonus?->id ?? ProjectBonus::factory(),
        ]);
    }
}
