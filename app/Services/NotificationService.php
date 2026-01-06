<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Donation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;

/**
 * Сервіс для управління повідомленнями та нотифікаціями
 */
class NotificationService
{
    /**
     * Надіслати повідомлення про новий донат автору проєкту (author receives)
     */
    public function notifyDonationReceived(Donation $donation): ?Notification
    {
        $project = $donation->project;

        // Для донатів на платформу немає автора
        if (! $project) {
            return null;
        }

        $user = $project->user;

        $donorName = $donation->is_anonymous
            ? 'Анонімний меценат'
            : ($donation->donor_name ?? $donation->user?->name ?? 'Меценат');

        $projectTitle = $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт';

        return $this->createNotification(
            user: $user,
            type: NotificationType::DonationReceived,
            title: [
                'uk' => 'Ваш проєкт підтримано!',
                'en' => 'Your project received support!',
            ],
            message: [
                'uk' => sprintf(
                    '%s підтримав ваш проєкт "%s" на суму %s %s',
                    $donorName,
                    $projectTitle,
                    number_format($donation->amount, 0, '.', ' '),
                    $donation->currency instanceof \App\Enums\Currency ? $donation->currency->value : $donation->currency
                ),
                'en' => sprintf(
                    '%s supported your project "%s" with %s %s',
                    $donorName,
                    $project->title['en'] ?? $projectTitle,
                    number_format($donation->amount, 0, '.', ' '),
                    $donation->currency instanceof \App\Enums\Currency ? $donation->currency->value : $donation->currency
                ),
            ],
            data: [
                'donation_id' => $donation->id,
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'amount' => $donation->amount,
                'currency' => $donation->currency instanceof \App\Enums\Currency ? $donation->currency->value : $donation->currency,
                'donor_name' => $donorName,
            ]
        );
    }

    /**
     * Надіслати підтвердження донатеру (donor receives)
     */
    public function notifyDonationMade(Donation $donation): ?Notification
    {
        // Тільки для авторизованих донатерів
        if (! $donation->user_id) {
            return null;
        }

        $project = $donation->project;

        if ($project) {
            $projectTitle = $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт';

            return $this->createNotification(
                user: $donation->user,
                type: NotificationType::DonationMade,
                title: [
                    'uk' => 'Ви підтримали проєкт',
                    'en' => 'You supported a project',
                ],
                message: [
                    'uk' => sprintf(
                        'Ви підтримали проєкт "%s" на суму %s %s. Дякуємо!',
                        $projectTitle,
                        number_format($donation->amount, 0, '.', ' '),
                        $donation->currency instanceof \App\Enums\Currency ? $donation->currency->value : $donation->currency
                    ),
                    'en' => sprintf(
                        'You supported the project "%s" with %s %s. Thank you!',
                        $project->title['en'] ?? $projectTitle,
                        number_format($donation->amount, 0, '.', ' '),
                        $donation->currency instanceof \App\Enums\Currency ? $donation->currency->value : $donation->currency
                    ),
                ],
                data: [
                    'donation_id' => $donation->id,
                    'project_id' => $project->id,
                    'project_slug' => $project->slug,
                    'amount' => $donation->amount,
                ]
            );
        }

        // Донат на платформу
        return $this->createNotification(
            user: $donation->user,
            type: NotificationType::DonationMade,
            title: [
                'uk' => 'Дякуємо за підтримку!',
                'en' => 'Thank you for your support!',
            ],
            message: [
                'uk' => sprintf(
                    'Ви підтримали платформу Save-Art на суму %s %s. Щиро дякуємо!',
                    number_format($donation->amount, 0, '.', ' '),
                    $donation->currency instanceof \App\Enums\Currency ? $donation->currency->value : $donation->currency
                ),
                'en' => sprintf(
                    'You supported Save-Art platform with %s %s. Thank you!',
                    number_format($donation->amount, 0, '.', ' '),
                    $donation->currency instanceof \App\Enums\Currency ? $donation->currency->value : $donation->currency
                ),
            ],
            data: [
                'donation_id' => $donation->id,
                'amount' => $donation->amount,
                'donation_type' => 'platform',
            ]
        );
    }

    /**
     * Надіслати повідомлення про схвалення проєкту
     */
    public function notifyProjectApproved(Project $project): Notification
    {
        $projectTitle = $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт';

        return $this->createNotification(
            user: $project->user,
            type: NotificationType::ProjectApproved,
            title: [
                'uk' => 'Ваш проєкт пройшов перевірку',
                'en' => 'Your project has been approved',
            ],
            message: [
                'uk' => sprintf(
                    'Ваш проєкт "%s" успішно пройшов модерацію та опублікований. Тепер проєкт доступний для перегляду та підтримки усіма користувачами. Бажаємо успіхів!',
                    $projectTitle
                ),
                'en' => sprintf(
                    'Your project "%s" has been approved and published. Now your project is available for viewing and support by all users. Good luck!',
                    $project->title['en'] ?? $projectTitle
                ),
            ],
            data: [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
            ]
        );
    }

    /**
     * Надіслати повідомлення про відхилення проєкту модератором (можна виправити)
     */
    public function notifyProjectModerationFailed(Project $project, ?string $reason = null): Notification
    {
        $projectTitle = $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт';

        $messageUk = sprintf('Ваш проєкт "%s" не пройшов перевірку.', $projectTitle);
        $messageEn = sprintf('Your project "%s" did not pass moderation.', $project->title['en'] ?? $projectTitle);

        if ($reason) {
            $messageUk .= ' Причина: '.$reason.'. Виправте зауваження та надішліть на повторну модерацію.';
            $messageEn .= ' Reason: '.$reason.'. Please fix the issues and resubmit for moderation.';
        }

        return $this->createNotification(
            user: $project->user,
            type: NotificationType::ModerationFailed,
            title: [
                'uk' => 'Ваш проєкт не пройшов перевірку',
                'en' => 'Your project did not pass moderation',
            ],
            message: [
                'uk' => $messageUk,
                'en' => $messageEn,
            ],
            data: [
                'project_id' => $project->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Надіслати повідомлення про остаточне відхилення проєкту
     */
    public function notifyProjectRejected(Project $project, ?string $reason = null): Notification
    {
        $projectTitle = $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт';

        $messageUk = sprintf(
            'Ваш проєкт "%s" остаточно відхилено. Щиро дякуємо за старанність, але він не пройшов перевірку. Врахуйте усі помилки та підготуйте новий проєкт.',
            $projectTitle
        );
        $messageEn = sprintf(
            'Your project "%s" has been permanently rejected. Thank you for your effort, but it did not pass moderation. Please consider the feedback and prepare a new project.',
            $project->title['en'] ?? $projectTitle
        );

        if ($reason) {
            $messageUk .= ' Причина: '.$reason;
            $messageEn .= ' Reason: '.$reason;
        }

        return $this->createNotification(
            user: $project->user,
            type: NotificationType::ProjectRejected,
            title: [
                'uk' => 'Ваш проєкт відхилено',
                'en' => 'Your project has been rejected',
            ],
            message: [
                'uk' => $messageUk,
                'en' => $messageEn,
            ],
            data: [
                'project_id' => $project->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Надіслати повідомлення про завершення збору коштів
     */
    public function notifyProjectFundingComplete(Project $project): Notification
    {
        $projectTitle = $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт';

        return $this->createNotification(
            user: $project->user,
            type: NotificationType::ProjectFundingComplete,
            title: [
                'uk' => 'Збір завершено!',
                'en' => 'Funding complete!',
            ],
            message: [
                'uk' => sprintf(
                    'Вітаємо! Ваш проєкт "%s" досяг мети збору. Тепер можна розпочинати роботу. Пам\'ятайте про документальне підтвердження виконаних робіт.',
                    $projectTitle
                ),
                'en' => sprintf(
                    'Congratulations! Your project "%s" has reached its funding goal. You can now start working. Remember to document your progress.',
                    $project->title['en'] ?? $projectTitle
                ),
            ],
            data: [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
                'collected' => $project->collected_amount ?? 0,
                'goal' => $project->budget_goal,
            ]
        );
    }

    /**
     * Надіслати повідомлення про завершення проєкту
     */
    public function notifyProjectCompleted(Project $project): Notification
    {
        $projectTitle = $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт';

        return $this->createNotification(
            user: $project->user,
            type: NotificationType::ProjectCompleted,
            title: [
                'uk' => 'Проєкт завершено',
                'en' => 'Project completed',
            ],
            message: [
                'uk' => sprintf('Ваш проєкт "%s" успішно завершено. Дякуємо за вашу творчість!', $projectTitle),
                'en' => sprintf('Your project "%s" has been completed. Thank you for your creativity!', $project->title['en'] ?? $projectTitle),
            ],
            data: [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
            ]
        );
    }

    /**
     * Надіслати повідомлення про нове повідомлення від адміністратора
     */
    public function notifyNewMessage(Message $message): Notification
    {
        return $this->createNotification(
            user: $message->user,
            type: NotificationType::Message,
            title: [
                'uk' => 'Нове повідомлення від адміністрації',
                'en' => 'New message from administration',
            ],
            message: [
                'uk' => $message->subject ? sprintf('Тема: %s', $message->subject) : 'Ви отримали нове повідомлення від адміністрації.',
                'en' => $message->subject ? sprintf('Subject: %s', $message->subject) : 'You received a new message from administration.',
            ],
            data: [
                'message_id' => $message->id,
                'project_id' => $message->project_id,
            ]
        );
    }

    /**
     * Надіслати системне сповіщення
     *
     * @param  array{uk: string, en: string}  $title
     * @param  array{uk: string, en: string}  $message
     */
    public function notifySystem(User $user, array $title, array $message, array $data = []): Notification
    {
        return $this->createNotification(
            user: $user,
            type: NotificationType::System,
            title: $title,
            message: $message,
            data: $data
        );
    }

    /**
     * Створити нотифікацію в базі даних
     *
     * @param  array{uk: string, en: string}  $title
     * @param  array{uk: string, en: string}  $message
     */
    public function createNotification(
        User $user,
        NotificationType $type,
        array $title,
        array $message,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Позначити всі нотифікації користувача як прочитані
     */
    public function markAllAsRead(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Позначити конкретну нотифікацію як прочитану
     */
    public function markAsRead(Notification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    /**
     * Отримати непрочитані нотифікації користувача
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Notification>
     */
    public function getUnreadNotifications(User $user, int $limit = 10)
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Отримати кількість непрочитаних нотифікацій
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Застарілий метод - використовуйте notifyDonationReceived
     *
     * @deprecated
     */
    public function notifyDonation(Donation $donation): ?Notification
    {
        return $this->notifyDonationReceived($donation);
    }
}
