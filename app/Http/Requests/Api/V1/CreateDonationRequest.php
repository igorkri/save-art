<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Currency;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Донатити можуть і гості, і авторизовані
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:10'],
            'currency' => ['required', Rule::enum(Currency::class)],

            'bonus_id' => ['nullable', 'exists:project_bonuses,id'],

            'is_anonymous' => ['boolean'],
            'donor_type' => ['required', Rule::enum(UserType::class)],

            // Для неавторизованих користувачів
            'donor_name' => [Rule::requiredIf(fn () => ! $this->user()), 'nullable', 'string', 'max:255'],
            'donor_email' => [Rule::requiredIf(fn () => ! $this->user()), 'nullable', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:20'],

            // Повідомлення від донатера
            'message' => ['nullable', 'string', 'max:500'],

            // Для юр. осіб
            'donor_company_name' => ['nullable', 'string', 'max:255'],
            'donor_edrpou' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Вкажіть суму донату',
            'amount.min' => 'Мінімальна сума донату — 10',
            'donor_name.required' => "Вкажіть ваше ім'я",
            'donor_email.required' => 'Вкажіть вашу email адресу',
        ];
    }
}
