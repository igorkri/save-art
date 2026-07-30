<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class OrderServiceRequest extends FormRequest
{
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
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'options' => ['nullable', 'array', 'max:50'],
            'options.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Вкажіть ім'я.",
            'email.required' => 'Вкажіть електронну пошту.',
            'email.email' => 'Некоректна електронна пошта.',
            'message.required' => 'Заповніть повідомлення.',
        ];
    }
}
