<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * Поля, які ніколи не потрапляють у лог у відкритому вигляді.
     *
     * @var array<int, string>
     */
    private const HIDDEN_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'token',
    ];

    /**
     * Логує кожен API-запит у канал, що відповідає ендпоінту
     * (storage/logs/api/{uri-ендпоінту}/{дата}.log).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $channel = $this->resolveChannel($request);
        $context = [
            'method' => $request->method(),
            'uri' => '/'.$request->path(),
            'ip' => $request->ip(),
            'user_id' => optional($request->user())->id,
            'input' => $request->except(self::HIDDEN_FIELDS),
        ];

        try {
            $response = $next($request);
        } catch (ValidationException $e) {
            Log::channel($channel)->warning('Помилка валідації', [
                ...$context,
                'errors' => $e->errors(),
            ]);

            throw $e;
        }

        $status = $response->getStatusCode();
        $logData = [...$context, 'status' => $status];

        if ($status >= 400) {
            $decoded = json_decode($response->getContent() ?: '', true);
            $logData['response'] = $decoded ?? $response->getContent();
        }

        $level = match (true) {
            $status >= 500 => 'error',
            $status >= 400 => 'warning',
            default => 'info',
        };

        Log::channel($channel)->{$level}(
            $status >= 400 ? 'Помилка запиту' : 'Запит оброблено',
            $logData
        );

        return $response;
    }

    /**
     * Формує (і за потреби реєструє на льоту) канал логування для конкретного ендпоінту,
     * щоб кожен ендпоінт писав у свою папку storage/logs/api/{endpoint}/ з файлом на день.
     */
    private function resolveChannel(Request $request): string
    {
        $route = $request->route();
        $pattern = $route ? $route->uri() : $request->path();

        $folder = trim(preg_replace('/[{}]/', '', $pattern), '/');
        $folder = $folder !== '' ? $folder : 'root';

        $channelName = 'api-endpoint-'.$folder;

        if (! config("logging.channels.{$channelName}")) {
            config(["logging.channels.{$channelName}" => [
                'driver' => 'daily',
                'path' => storage_path("logs/{$folder}/log.log"),
                'level' => 'debug',
                'days' => 30,
                'replace_placeholders' => true,
            ]]);
        }

        return $channelName;
    }
}
