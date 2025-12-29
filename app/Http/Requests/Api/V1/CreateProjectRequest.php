<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\Currency;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Основні поля
            'user_type' => ['required', Rule::enum(UserType::class)],

            'title' => ['required', 'array'],
            'title.uk' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            'short_description' => ['nullable', 'array'],
            'short_description.uk' => ['nullable', 'string', 'max:1000'],
            'short_description.en' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable', 'image', 'max:15360'], // 15MB

            // Категорія
            'art_category' => ['required', Rule::enum(ArtCategory::class)],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array'],
            'tags.uk' => ['nullable', 'string', 'max:500'],
            'tags.en' => ['nullable', 'string', 'max:500'],

            // Бюджет
            'currency' => ['required', Rule::enum(Currency::class)],
            'budget_goal' => ['required', 'numeric', 'min:100'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            // Статті бюджету
            'budget_items' => ['nullable', 'array'],
            'budget_items.*.name' => ['required_with:budget_items', 'string', 'max:255'],
            'budget_items.*.amount' => ['required_with:budget_items', 'numeric', 'min:0'],

            // Характеристики
            'characteristics' => ['nullable', 'array'],
            'characteristics.*.name' => ['required_with:characteristics', 'string', 'max:255'],
            'characteristics.*.value' => ['required_with:characteristics', 'string', 'max:500'],

            // Додаткова інформація
            'additional_info' => ['nullable', 'array'],
            'additional_info.uk' => ['nullable', 'string', 'max:10000'],
            'additional_info.en' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.uk.required' => 'Назва проєкту українською є обов\'язковою',
            'art_category.required' => 'Оберіть галузь мистецтва',
            'budget_goal.required' => 'Вкажіть ціль збору',
            'budget_goal.min' => 'Мінімальна ціль збору — 100',
            'cover.max' => 'Максимальний розмір обкладинки — 15 МБ',
        ];
    }
}
