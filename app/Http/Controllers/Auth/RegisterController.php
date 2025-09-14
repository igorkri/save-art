<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Показать форму регистрации
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Обработать регистрацию пользователя
     *
     * @param  StoreUserRequest  $request  - автоматически валидирует данные
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        // Данные уже провалидированы благодаря StoreUserRequest
        $validated = $request->validated();

        // Создаем пользователя
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Автоматически входим в систему
        Auth::login($user);

        // Flash сообщение
        session()->flash('success', 'Ласкаво просимо, '.$user->name.'!');

        // Перенаправляем на главную страницу
        return redirect('/')->with('status', 'Реєстрацію завершено успішно!');
    }

    /**
     * Альтернативный способ - ручная валидация в контроллере
     */
    public function storeManual(): RedirectResponse
    {
        // Ручная валидация (не рекомендуется для сложных форм)
        $validated = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Поле імені обов\'язкове',
            'email.unique' => 'Цей email вже зареєстровано',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect('/');
    }

    /**
     * Пример работы с дополнительными данными из запроса
     */
    public function storeAdvanced(StoreUserRequest $request): RedirectResponse
    {
        // Получаем только нужные поля
        $userData = $request->safe()->only(['name', 'email']);

        // Или получаем все валидированные данные
        $allValidated = $request->validated();

        // Получаем дополнительные данные, не включенные в валидацию
        $additionalData = $request->collect()->except(['password', 'password_confirmation']);

        // Создание пользователя с дополнительными действиями
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($allValidated['password']),
        ]);

        // Логирование регистрации
        \Log::info('Новый пользователь зарегистрирован', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        Auth::login($user);

        return redirect('/')->with([
            'success' => 'Ласкаво просимо!',
            'user' => $user,
        ]);
    }
}
