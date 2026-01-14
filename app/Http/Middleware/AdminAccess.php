<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, авторизован ли пользователь
        if (! auth()->check()) {
            abort(401, 'Unauthorized');
        }

        $user = auth()->user();

        // Проверяем, имеет ли пользователь права доступа к админ панели
        if (! $user->isAdmin()) {
            abort(403, 'Access denied. Administrator role required.');
        }

        return $next($request);
    }
}
