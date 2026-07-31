<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Визначає, з якого фронтенду (save-art / art-ua-info / art-ua.com) прийшов
 * запит, щоб посилання в листах (підтвердження email, скидання пароля тощо)
 * вели на правильний домен, а не завжди на дефолтний FRONTEND_URL. Довіряємо
 * лише доменам зі списку CORS_ALLOWED_ORIGINS.
 */
class FrontendUrlResolver
{
    public static function resolve(Request $request): string
    {
        $origin = $request->headers->get('Origin') ?: $request->headers->get('Referer');

        if ($origin) {
            $parts = parse_url($origin);

            if ($parts && isset($parts['scheme'], $parts['host'])) {
                $originBase = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

                foreach (config('cors.allowed_origins', []) as $allowedOrigin) {
                    if (rtrim($allowedOrigin, '/') === $originBase) {
                        return $originBase;
                    }
                }
            }
        }

        return config('app.frontend_url');
    }
}
