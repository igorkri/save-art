<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Лист підтвердження email із посиланням на конкретний фронтенд (save-art /
 * art-ua-info / art-ua.com), з якого прийшла реєстрація — на відміну від
 * стандартної Illuminate\Auth\Notifications\VerifyEmail, яка через глобальний
 * VerifyEmail::createUrlUsing (App\Providers\AppServiceProvider) завжди веде
 * на дефолтний FRONTEND_URL, незалежно від того, звідки реєструвався користувач.
 */
class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ?string $frontendUrl = null) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $texts = $this->texts();
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject($texts['subject'])
            ->greeting($texts['greeting'])
            ->line($texts['line1'])
            ->action($texts['action'], $url)
            ->line($texts['line2'])
            ->line($texts['line3'])
            ->theme('saveart');
    }

    private function verificationUrl(mixed $notifiable): string
    {
        $backendUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $query = parse_url($backendUrl, PHP_URL_QUERY);
        $base = $this->frontendUrl ?? config('app.frontend_url');

        return rtrim($base, '/')
            .'/verify-email/'.$notifiable->getKey().'/'.sha1($notifiable->getEmailForVerification())
            .'?'.$query;
    }

    /**
     * @return array<string, string>
     */
    private function texts(): array
    {
        if (app()->getLocale() === 'en') {
            return [
                'subject' => 'Confirm your email — save-art.in.ua',
                'greeting' => 'Hello!',
                'line1' => 'Please confirm your email address to finish setting up your profile.',
                'action' => 'Confirm email and continue',
                'line2' => 'This link will expire in 60 minutes.',
                'line3' => 'If you did not create an account, no further action is required.',
            ];
        }

        return [
            'subject' => 'Підтвердження email — save-art.in.ua',
            'greeting' => 'Вітаємо!',
            'line1' => 'Підтвердіть свою електронну пошту, щоб продовжити заповнення профілю.',
            'action' => 'Підтвердити email і продовжити',
            'line2' => 'Це посилання дійсне протягом 60 хвилин.',
            'line3' => 'Якщо ви не реєструвалися на платформі — жодних додаткових дій не потрібно.',
        ];
    }
}
