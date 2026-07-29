<?php

namespace App\Http\Controllers\ArtInfo;

use OpenApi\Annotations as OA;

/**
 * Документує реальні /v1/art-ua-info/auth/* маршрути (routes/api-art-ua-info.php),
 * реалізовані спільними контролерами App\Http\Controllers\Api\V1\Auth\* (ті самі,
 * що обслуговують /v1/auth/* для save-art). Auth-логіка навмисно не дублюється —
 * форк security-критичного коду (хешування паролів, видача токенів) заради
 * єдиної відмінності у шляху лише додає ризик непомітно розійтися між копіями.
 * Клас використовується тільки для OpenAPI анотацій, роут не реєструє.
 *
 * @OA\Post(
 *     path="/v1/art-ua-info/auth/login",
 *     operationId="artUaInfoLogin",
 *     tags={"Auth"},
 *     summary="Авторизація користувача",
 *     description="Авторизація користувача за email та паролем. Повертає Bearer токен для подальших запитів.",
 *     security={{"apiKey":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123"),
 *             @OA\Property(property="device_name", type="string", example="art-ua-info-web")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Успішна авторизація",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="Авторизація успішна"),
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Іван Петренко"),
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="slug", type="string", example="ivan-petrenko"),
 *                 @OA\Property(property="role", type="string", example="user")
 *             ),
 *             @OA\Property(property="token", type="string", example="1|abc123...")
 *         )
 *     ),
 *
 *     @OA\Response(response=403, description="Невалідний або відсутній API ключ"),
 *     @OA\Response(response=422, description="Невірні дані авторизації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/v1/art-ua-info/auth/register",
 *     operationId="artUaInfoRegister",
 *     tags={"Auth"},
 *     summary="Реєстрація нового користувача",
 *     description="Створює новий обліковий запис користувача та повертає токен авторизації",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"name", "email", "password", "password_confirmation"},
 *
 *             @OA\Property(property="name", type="string", minLength=2, maxLength=255, example="Іван Петренко"),
 *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com"),
 *             @OA\Property(property="password", type="string", format="password", minLength=8, example="SecurePass123"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123"),
 *             @OA\Property(property="device_name", type="string", example="art-ua-info-web")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Успішна реєстрація",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="message", type="string", example="Реєстрація успішна"),
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Іван Петренко"),
 *                 @OA\Property(property="email", type="string", example="ivan@example.com"),
 *                 @OA\Property(property="slug", type="string", example="ivan-petrenko-abc123")
 *             ),
 *             @OA\Property(property="token", type="string", example="1|abc123xyz...")
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Помилка валідації", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
 * )
 *
 * @OA\Post(
 *     path="/v1/art-ua-info/auth/logout",
 *     operationId="artUaInfoLogout",
 *     tags={"Auth"},
 *     summary="Вихід з системи",
 *     description="Видаляє поточний токен авторизації",
 *     security={{"sanctum":{}, "apiKey":{}}},
 *
 *     @OA\Response(response=200, description="Успішний вихід", @OA\JsonContent(@OA\Property(property="message", type="string", example="Вихід виконано успішно"))),
 *     @OA\Response(response=401, description="Не авторизовано")
 * )
 *
 * @OA\Get(
 *     path="/v1/art-ua-info/auth/me",
 *     operationId="artUaInfoMe",
 *     tags={"Auth"},
 *     summary="Поточний користувач",
 *     description="Повертає дані авторизованого користувача",
 *     security={{"sanctum":{}, "apiKey":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Дані користувача",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Іван Петренко"),
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="slug", type="string", example="ivan-petrenko"),
 *                 @OA\Property(property="role", type="string", example="user"),
 *                 @OA\Property(property="avatar_url", type="string", nullable=true),
 *                 @OA\Property(property="has_seen_new_project_hint", type="boolean", example=false),
 *                 @OA\Property(property="created_at", type="string", format="date-time")
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Не авторизовано")
 * )
 *
 * @OA\Post(
 *     path="/v1/art-ua-info/auth/forgot-password",
 *     operationId="artUaInfoForgotPassword",
 *     tags={"Auth"},
 *     summary="Запит на скидання пароля",
 *     description="Надсилає email з посиланням для скидання пароля",
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="email", type="string", format="email", example="user@example.com"))),
 *
 *     @OA\Response(response=200, description="Посилання надіслано", @OA\JsonContent(@OA\Property(property="message", type="string"))),
 *     @OA\Response(response=422, description="Помилка валідації")
 * )
 *
 * @OA\Post(
 *     path="/v1/art-ua-info/auth/reset-password",
 *     operationId="artUaInfoResetPassword",
 *     tags={"Auth"},
 *     summary="Скидання пароля",
 *     description="Скидає пароль за допомогою токена з email",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="token", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string", minLength=8),
 *             @OA\Property(property="password_confirmation", type="string")
 *         )
 *     ),
 *
 *     @OA\Response(response=200, description="Пароль змінено", @OA\JsonContent(@OA\Property(property="message", type="string"))),
 *     @OA\Response(response=422, description="Помилка валідації або невалідний токен")
 * )
 *
 * @OA\Post(
 *     path="/v1/art-ua-info/auth/email/verification-notification",
 *     operationId="artUaInfoResendEmailVerification",
 *     tags={"Auth"},
 *     summary="Повторна відправка листа підтвердження email",
 *     security={{"sanctum":{}, "apiKey":{}}},
 *
 *     @OA\Response(response=200, description="Лист надіслано"),
 *     @OA\Response(response=401, description="Не авторизовано")
 * )
 */
class AuthDocs
{
    // Цей клас використовується тільки для OpenAPI анотацій
}
