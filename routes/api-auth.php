
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// API: Логин
Route::post('/api/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();
    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Неверные данные'], 401);
    }

    $token = $user->createToken('web')->plainTextToken;
    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});

// API: Регистрация
Route::post('/api/register', function (Request $request) {
    $data = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
    ]);
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ]);
    $token = $user->createToken('web')->plainTextToken;
    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);
});

// API: Logout
Route::middleware('auth:sanctum')->post('/api/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Выход выполнен']);
});

// API: Получить текущего пользователя
Route::middleware('auth:sanctum')->get('/api/user', function (Request $request) {
    return $request->user();
});
