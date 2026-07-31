<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\FrontendUrlResolver;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class RegisterController extends Controller
{
    /**
     * Реєстрація нового користувача
     *
     * @OA\Post(
     *     path="/v1/auth/register",
     *     operationId="register",
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
     *             @OA\Property(property="name", type="string", minLength=2, maxLength=255, example="Іван Петренко", description="Повне ім'я користувача"),
     *             @OA\Property(property="email", type="string", format="email", example="ivan@example.com", description="Email (унікальний)"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="SecurePass123", description="Пароль (мін. 8 символів)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123", description="Підтвердження пароля"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 15 Pro", description="Назва пристрою для токена")
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
     *     @OA\Response(
     *         response=422,
     *         description="Помилка валідації",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'full_name' => ['uk' => $data['name'], 'en' => $data['name']],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'slug' => Str::slug($data['name']).'-'.Str::random(6),
            'role' => UserRole::User,
        ]);

        // Надсилаємо лист із посиланням для підтвердження email. Дзвонимо напряму,
        // а не через event(new Registered($user)) — у цьому застосунку одночасно
        // активні і старий App\Providers\EventServiceProvider (config/app.php),
        // і автоматичний EventServiceProvider з bootstrap/app.php, тому подія
        // Registered дублює SendEmailVerificationNotification і лист іде двічі.
        // Використовуємо VerifyEmailNotification (не $user->sendEmailVerificationNotification())
        // з фронтендом, визначеним по Origin/Referer запиту, — інакше посилання
        // завжди веде на дефолтний FRONTEND_URL, навіть якщо реєстрація прийшла
        // з art-ua-info чи art-ua.com.
        $user->notify(new VerifyEmailNotification(FrontendUrlResolver::resolve($request)));

        // Створюємо токен для API
        $token = $user->createToken(
            $request->input('device_name', 'api-token')
        )->plainTextToken;

        return response()->json([
            'message' => 'Реєстрація успішна',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'slug' => $user->slug,
            ],
            'token' => $token,
        ], 201);
    }
}
