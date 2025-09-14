<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessFilament
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Если пользователь не авторизован, Laravel автоматически перенаправит на страницу входа
        if (!$user) {
            return $next($request);
        }

        // Проверяем, имеет ли пользователь доступ к админке
        if (!$user->hasAnyRole([UserRole::Developer, UserRole::Admin, UserRole::Moderator])) {
            abort(403, 'У вас немає доступу до адмін-панелі. Зверніться до адміністратора.');
        }

        return $next($request);
    }
}
