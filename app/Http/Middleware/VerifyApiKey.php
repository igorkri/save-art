<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * Перевіряє наявність та валідність API-ключа.
     * Ключ передається в заголовку X-Api-Key.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');
        $validKey = config('services.api_key');

        // Якщо ключ не налаштований — пропускаємо (для локальної розробки)
        if (empty($validKey)) {
            return $next($request);
        }

        if (empty($apiKey) || ! hash_equals($validKey, $apiKey)) {
            return response()->json([
                'message' => 'Invalid or missing API key.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
