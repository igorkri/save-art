<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Перевірка, чи це API запит
        $isApiRequest = fn (Request $request) => $request->expectsJson() || $request->is('api/*');

        // Обробка помилок автентифікації
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => __('auth.unauthenticated'),
                    'error' => 'Unauthenticated',
                ], 401);
            }
        });

        // Обробка помилок валідації
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => __('validation.failed'),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Обробка 404 - Не знайдено
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => __('errors.not_found'),
                    'error' => 'Not Found',
                ], 404);
            }
        });

        // Обробка 405 - Метод не дозволено
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => __('errors.method_not_allowed'),
                    'error' => 'Method Not Allowed',
                ], 405);
            }
        });

        // Обробка 403 - Доступ заборонено
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => $e->getMessage() ?: __('errors.forbidden'),
                    'error' => 'Forbidden',
                ], 403);
            }
        });

        // Обробка інших HTTP помилок
        $exceptions->render(function (HttpException $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                return response()->json([
                    'message' => $e->getMessage() ?: __('errors.http_error'),
                    'error' => 'HTTP Error',
                ], $e->getStatusCode());
            }
        });

        // Обробка всіх інших виключень (500 помилки)
        $exceptions->render(function (Throwable $e, Request $request) use ($isApiRequest) {
            if ($isApiRequest($request)) {
                $statusCode = 500;
                $message = app()->isProduction()
                    ? __('errors.server_error')
                    : $e->getMessage();

                return response()->json([
                    'message' => $message,
                    'error' => 'Server Error',
                    ...(! app()->isProduction() ? [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->toArray(),
                    ] : []),
                ], $statusCode);
            }
        });
    })->create();
