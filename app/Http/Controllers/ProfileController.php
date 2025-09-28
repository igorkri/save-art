<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function new()
    {
        // Только для авторизованных пользователей с ролью 'user'
        $user = auth()->user();
        if (!$user || $user->role->value !== \App\UserRole::User->value) {
            abort(403, 'Доступ заборонено');
        }
        
        return view('profile.new');
    }


    public function store(Request $request)
    {
        // Только для авторизованных пользователей с ролью 'user'
        $user = auth()->user();
        // dd($user->role->value, \App\UserRole::User->value);
        if (!$user || $user->role->value !== \App\UserRole::User->value) {
            abort(403, 'Доступ заборонено');
        }

        $validated = $request->validate([
            'art' => 'required|in:' . \App\UserRole::Mecenat->value . ',' . \App\UserRole::Owner->value,
        ]);

        // Сохранение предпочтений пользователя
        $user->role = $request->input('art');
        $user->save();

        // Логика для пользователей, которые хотят создавать и поддерживать искусство
        // session()->flash('notification', [
        //     'title' => 'Заголовок',
        //     'message' => 'Текст уведомления',
        //     'class' => 'green', // или 'red', 'yellow' и т.д.
        //     'autoClose' => true, // или false, если не нужно автоскрытие
        // ]);

        return redirect()->route('profile.new')->with('success', __('profile_new.saved'));
        // return redirect()->back()->with('success', __('profile_new.saved'));
    }
}
