<?php

namespace App;

use App\Models\User;
use Illuminate\Support\Facades\Auth as LaravelAuth;
use Illuminate\Support\Facades\Hash;

class Auth
{
    public static function login($email, $password)
    {
        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return [false, 'Невірні дані'];
        }
        $token = $user->createToken('web')->plainTextToken;
        session(['api_token' => $token]);
        LaravelAuth::login($user);
        return [true, $user];
    }

    public static function register($data)
    {
        // $data: ['email' => ..., 'password' => ..., ...]
        if (User::where('email', $data['email'])->exists()) {
            return [false, 'Email вже використовується'];
        }
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            // додати інші поля за потреби
        ]);
        $token = $user->createToken('web')->plainTextToken;
        session(['api_token' => $token]);
        LaravelAuth::login($user);
        return [true, $user];
    }

    public static function resetPassword($email, $newPassword)
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return [false, 'Користувача не знайдено'];
        }
        $user->password = Hash::make($newPassword);
        $user->save();
        return [true, $user];
    }
}

