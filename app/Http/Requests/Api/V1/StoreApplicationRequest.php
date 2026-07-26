<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Публічна форма (кнопка "Відправити заявку" у футері) — доступна без авторизації.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'about' => ['required', 'string', 'max:2000'],
            'resume' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => "Вкажіть повне ім'я",
            'name.max' => "Ім'я — максимум 255 символів",
            'email.required' => 'Вкажіть електронну пошту',
            'email.email' => 'Введіть коректну електронну пошту',
            'email.max' => 'Пошта — максимум 255 символів',
            'phone.required' => 'Вкажіть номер телефону',
            'phone.max' => 'Телефон — максимум 30 символів',
            'about.required' => 'Розкажіть коротко про себе',
            'about.max' => 'Опис — максимум 2000 символів',
            'resume.file' => 'Резюме має бути файлом',
            'resume.max' => 'Максимальний розмір резюме — 10MB',
            'resume.mimes' => 'Резюме приймається лише у форматі PDF, DOC або DOCX',
        ];
    }
}
