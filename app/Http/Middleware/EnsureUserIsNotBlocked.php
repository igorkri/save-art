<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Забороняє заблокованому користувачу керувати проєктами та здійснювати донати,
 * поки блокування активне. Перегляд власних даних та вихід з акаунту залишаються доступними.
 */
class EnsureUserIsNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isActuallyBlocked()) {
            return response()->json([
                'message' => 'Ваш профіль тимчасово заблоковано. Ця дія недоступна.',
            ], 403);
        }

        return $next($request);
    }
}
