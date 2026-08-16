<?php

namespace App\Http\Controllers\Api\V1\ArtUaInfo;

use App\Enums\ProfileType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\FrontendUrlResolver;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Реєстрація для art-ua-info — окремий контролер від Api\V1\Auth\RegisterController
 * (save-art). На відміну від save-art, тут одразу проставляється
 * profile_type = Artist: art-ua-info — платформа виключно для митців, і на
 * відміну від save-art тут немає власного кроку вибору ролі (немає патронів,
 * немає форми зміни типу профілю). Без дефолту тут кабінет користувача був би
 * назавжди недоступний — profile_type просто нема звідки взяти.
 */
class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'full_name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'slug' => Str::slug($data['name']).'-'.Str::random(6),
            'role' => UserRole::User,
            'profile_type' => ProfileType::Artist,
        ]);

        $user->notify(new VerifyEmailNotification(FrontendUrlResolver::resolve($request)));

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
