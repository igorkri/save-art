<?php

namespace App\Providers;

use App\Models\Donation;
use App\Models\Project;
use App\Observers\DonationObserver;
use App\Observers\ProjectObserver;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Реєструємо observers для автоматичних сповіщень
        Donation::observe(DonationObserver::class);
        Project::observe(ProjectObserver::class);

        // Редирект з /uk/... на /... якщо основна мова
        \Illuminate\Support\Facades\Route::matched(function ($event) {
            $request = request();
            if ($request->segment(1) === 'uk') {
                $url = $request->fullUrl();
                $newUrl = preg_replace('#/uk($|/)#', '/', $url, 1);
                if ($newUrl !== $url) {
                    header('Location: '.$newUrl, true, 301);
                    exit;
                }
            }
        });

        $this->configureEmailVerification();
    }

    /**
     * Лист для підтвердження email веде на сторінку фронтенду (не на бекенд),
     * яка сама викликає підписаний verification.verify ендпоінт і, після
     * успіху, перенаправляє користувача на заповнення профілю.
     */
    private function configureEmailVerification(): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $backendUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            $query = parse_url($backendUrl, PHP_URL_QUERY);

            return rtrim(config('app.frontend_url'), '/')
                .'/verify-email/'.$notifiable->getKey().'/'.sha1($notifiable->getEmailForVerification())
                .'?'.$query;
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            $texts = app()->getLocale() === 'en' ? [
                'subject' => 'Confirm your email — save-art.in.ua',
                'greeting' => 'Hello!',
                'line1' => 'Please confirm your email address to finish setting up your profile.',
                'action' => 'Confirm email and continue',
                'line2' => 'This link will expire in 60 minutes.',
                'line3' => 'If you did not create an account, no further action is required.',
            ] : [
                'subject' => 'Підтвердження email — save-art.in.ua',
                'greeting' => 'Вітаємо!',
                'line1' => 'Підтвердіть свою електронну пошту, щоб продовжити заповнення профілю.',
                'action' => 'Підтвердити email і продовжити',
                'line2' => 'Це посилання дійсне протягом 60 хвилин.',
                'line3' => 'Якщо ви не реєструвалися на платформі — жодних додаткових дій не потрібно.',
            ];

            return (new MailMessage)
                ->subject($texts['subject'])
                ->greeting($texts['greeting'])
                ->line($texts['line1'])
                ->action($texts['action'], $url)
                ->line($texts['line2'])
                ->line($texts['line3'])
                ->theme('saveart');
        });
    }
}
