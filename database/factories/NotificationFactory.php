<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(NotificationType::cases()),
            'title' => fake()->sentence(4),
            'message' => fake()->optional()->paragraph(),
            'data' => null,
            'read_at' => null,
        ];
    }

    /**
     * Вказати користувача для сповіщення
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Позначити як прочитане
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Сповіщення про донат
     */
    public function donation(array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::Donation,
            'title' => 'Новий донат',
            'message' => 'Ваш проєкт отримав новий донат!',
            'data' => $data,
        ]);
    }

    /**
     * Сповіщення про модерацію
     */
    public function moderation(array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::Moderation,
            'title' => 'Модерація проєкту',
            'message' => 'Ваш проєкт пройшов модерацію.',
            'data' => $data,
        ]);
    }

    /**
     * Сповіщення про схвалення проєкту
     */
    public function projectApproved(array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::ProjectApproved,
            'title' => 'Проєкт схвалено!',
            'message' => 'Вітаємо! Ваш проєкт пройшов модерацію і опубліковано.',
            'data' => $data,
        ]);
    }

    /**
     * Сповіщення про відхилення проєкту
     */
    public function projectRejected(array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::ProjectRejected,
            'title' => 'Проєкт відхилено',
            'message' => 'На жаль, ваш проєкт не пройшов модерацію.',
            'data' => $data,
        ]);
    }

    /**
     * Системне сповіщення
     */
    public function system(?string $message = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NotificationType::System,
            'title' => 'Системне повідомлення',
            'message' => $message ?? 'Інформаційне повідомлення від системи.',
        ]);
    }
}
