<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[SensitiveParameter] private readonly string $token,
    ) {}

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
        $url = $this->resetUrl($notifiable);
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject($texts['subject'])
            ->greeting($texts['greeting'])
            ->line($texts['line1'])
            ->action($texts['action'], $url)
            ->line(str_replace(':count', (string) $expireMinutes, $texts['line2']))
            ->line($texts['line3'])
            ->theme('saveart');
    }

    private function resetUrl(mixed $notifiable): string
    {
        return rtrim(config('app.frontend_url'), '/')
            .'/reset-password?token='.$this->token
            .'&email='.urlencode($notifiable->getEmailForPasswordReset());
    }

    /**
     * @return array<string, string>
     */
    private function texts(): array
    {
        if (app()->getLocale() === 'en') {
            return [
                'subject' => 'Password reset — save-art.in.ua',
                'greeting' => 'Hello!',
                'line1' => 'You are receiving this email because we received a password reset request for your account.',
                'action' => 'Reset password',
                'line2' => 'This password reset link will expire in :count minutes.',
                'line3' => 'If you did not request a password reset, no further action is required.',
            ];
        }

        return [
            'subject' => 'Скидання пароля — save-art.in.ua',
            'greeting' => 'Вітаємо!',
            'line1' => 'Ви отримали цей лист, тому що надійшов запит на скидання пароля для вашого облікового запису.',
            'action' => 'Скинути пароль',
            'line2' => 'Це посилання дійсне протягом :count хвилин.',
            'line3' => 'Якщо ви не запитували скидання пароля — жодних додаткових дій не потрібно.',
        ];
    }
}
