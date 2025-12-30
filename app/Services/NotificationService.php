<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Donation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Сервіс для управління повідомленнями та нотифікаціями
 */
class NotificationService
{
    /**
     * Надіслати повідомлення про новий донат автору проєкту
     */
    public function notifyDonation(Donation $donation): Notification
    {
        $project = $donation->project;
        $user = $project->user;

        $donorName = $donation->is_anonymous
            ? 'Анонімний меценат'
            : ($donation->donor_name ?? $donation->user?->name ?? 'Меценат');

        return $this->createNotification(
            user: $user,
            type: NotificationType::Donation,
            title: 'Новий донат!',
            message: sprintf(
                'Ви отримали донат %s %s від %s для проєкту "%s"',
                number_format($donation->amount, 0, '.', ' '),
                $donation->currency->value,
                $donorName,
                $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт'
            ),
            data: [
                'donation_id' => $donation->id,
                'project_id' => $project->id,
                'amount' => $donation->amount,
                'currency' => $donation->currency->value,
            ]
        );
    }

    /**
     * Надіслати повідомлення про схвалення проєкту
     */
    public function notifyProjectApproved(Project $project): Notification
    {
        return $this->createNotification(
            user: $project->user,
            type: NotificationType::Moderation,
            title: 'Проєкт схвалено!',
            message: sprintf(
                'Ваш проєкт "%s" успішно пройшов модерацію та опублікований.',
                $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт'
            ),
            data: [
                'project_id' => $project->id,
                'project_slug' => $project->slug,
            ]
        );
    }

    /**
     * Надіслати повідомлення про відхилення проєкту
     */
    public function notifyProjectRejected(Project $project, ?string $reason = null): Notification
    {
        $message = sprintf(
            'Ваш проєкт "%s" відхилено модератором.',
            $project->title['uk'] ?? $project->title['en'] ?? 'Проєкт'
        );

        if ($reason) {
            $message .= ' Причина: '.$reason;
        }

        return $this->createNotification(
            user: $project->user,
            type: NotificationType::Moderation,
            title: 'Проєкт відхилено',
            message: $message,
            data: [
                'project_id' => $project->id,
                'reason' => $reason,
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
            title: 'Нове повідомлення від адміністрації',
            message: $message->subject
                ? sprintf('Тема: %s', $message->subject)
                : 'Ви отримали нове повідомлення від адміністрації.',
            data: [
                'message_id' => $message->id,
                'project_id' => $message->project_id,
            ]
        );
    }

    /**
     * Надіслати системне сповіщення
     */
    public function notifySystem(User $user, string $title, string $message, array $data = []): Notification
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
     */
    public function createNotification(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
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
     * Надіслати email-повідомлення (заготовка для інтеграції з Mailgun/SES)
     * TODO: Інтегрувати з реальним email провайдером
     */
    public function sendEmail(User $user, string $subject, string $content): bool
    {
        // TODO: Реалізувати відправку через Mail facade
        // Mail::to($user->email)->send(new GenericNotification($subject, $content));

        return true;
    }

    /**
     * Надіслати push-повідомлення (заготовка для інтеграції)
     * TODO: Інтегрувати з Firebase/OneSignal
     */
    public function sendPush(User $user, string $title, string $body, array $data = []): bool
    {
        // TODO: Реалізувати push-нотифікації

        return true;
    }
}
