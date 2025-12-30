<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'admin_id' => null,
            'project_id' => null,
            'content' => fake()->paragraph(),
            'direction' => 'user_to_admin',
            'subject' => fake()->optional(0.5)->sentence(),
            'read_at' => null,
        ];
    }

    /**
     * Повідомлення від адміністратора до користувача
     */
    public function fromAdmin(?User $admin = null): static
    {
        return $this->state(fn (array $attributes) => [
            'admin_id' => $admin?->id ?? User::factory()->admin(),
            'direction' => 'admin_to_user',
        ]);
    }

    /**
     * Повідомлення від користувача до адміністратора
     */
    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'user_to_admin',
        ]);
    }

    /**
     * Прочитане повідомлення
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Непрочитане повідомлення
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Повідомлення пов'язане з проєктом
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
            'user_id' => $project->user_id,
        ]);
    }

    /**
     * Повідомлення з певною темою
     */
    public function withSubject(string $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => $subject,
        ]);
    }
}
