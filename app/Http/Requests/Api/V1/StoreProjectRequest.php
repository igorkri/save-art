<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Currency;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для створення проекту (новий/чернетка) з динамічними правилами валідації
 */
class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Валюта проєкту наразі підтримується лише USD — примусово перезаписуємо
     * будь-яке значення, що прийшло з клієнта.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['currency' => Currency::USD->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $status = $this->input('status', 'new');
        $isDraft = in_array($status, ['new', 'draft']);

        return [
            // ========== Обов'язкові поля для всіх типів ==========
            'status' => ['nullable', 'string', Rule::enum(ProjectStatus::class)],
            'local_id' => ['nullable', 'string', 'max:100'],

            // ========== Основні поля проекту ==========
            'user_type' => [$isDraft ? 'nullable' : 'required', Rule::enum(UserType::class)],

            'title' => [$isDraft ? 'nullable' : 'required', 'array'],
            'title.uk' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            'short_description' => ['nullable', 'array'],
            'short_description.uk' => ['nullable', 'string', 'max:1000'],
            'short_description.en' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable'], // Может быть файл или Base64

            // Категорія - обязательно для публикации, опционально для черновика
            'art_category' => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array'],
            'tags.uk' => ['nullable', 'string', 'max:500'],
            'tags.en' => ['nullable', 'string', 'max:500'],

            // ========== Бюджет та валюта ==========
            'currency' => [$isDraft ? 'nullable' : 'required', Rule::enum(Currency::class)],
            'budget_goal' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:100', 'max:999999999'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            // ========== Структуровані дані ==========
            'budget_items' => ['nullable', 'array', 'max:50'],
            'budget_items.*.name' => ['sometimes', 'array'],
            'budget_items.*.name.uk' => ['sometimes', 'string', 'max:255'],
            'budget_items.*.name.en' => ['nullable', 'string', 'max:255'],
            'budget_items.*.amount' => ['sometimes', 'numeric', 'min:0'],

            'additional_info' => ['nullable', 'array'],
            'additional_info.uk' => ['nullable', 'string', 'max:5000'],
            'additional_info.en' => ['nullable', 'string', 'max:5000'],

            // ========== Контент-блоки ==========
            'content_blocks' => ['nullable', 'array', 'max:50'],
            'content_blocks.*.type' => ['sometimes', 'string', 'in:heading,paragraph,image'],
            'content_blocks.*.heading_level' => ['sometimes', 'string', 'in:h1,h2,h3,h4,h5,h6'],
            'content_blocks.*.heading_text' => ['sometimes', 'array'],
            'content_blocks.*.paragraph_text' => ['sometimes', 'array'],
            'content_blocks.*.image' => ['sometimes', 'string'],
            'content_blocks.*.image_alt' => ['sometimes', 'array'],

            // ========== Етапи (опціонально для чернеток) ==========
            'stages' => ['nullable', 'array'],
            'stages.*.title' => ['sometimes', 'array'],
            'stages.*.title.uk' => ['sometimes', 'string', 'max:255'],
            'stages.*.title.en' => ['nullable', 'string', 'max:255'],
            'stages.*.description' => ['sometimes', 'array'],
            'stages.*.description.uk' => ['sometimes', 'string'],
            'stages.*.description.en' => ['nullable', 'string'],
            'stages.*.days_planned' => ['sometimes', 'integer', 'min:1'],
            'stages.*.budget_planned' => ['sometimes', 'numeric', 'min:0'],
            'stages.*.order' => ['nullable', 'integer', 'min:0'],

            // ========== Бонуси (опціонально для чернеток) ==========
            'bonuses' => ['nullable', 'array'],
            'bonuses.*.title' => ['sometimes', 'array'],
            'bonuses.*.title.uk' => ['sometimes', 'string', 'max:255'],
            'bonuses.*.title.en' => ['nullable', 'string', 'max:255'],
            'bonuses.*.description' => ['sometimes', 'array'],
            'bonuses.*.description.uk' => ['sometimes', 'string'],
            'bonuses.*.description.en' => ['nullable', 'string'],
            'bonuses.*.min_donation' => ['sometimes', 'numeric', 'min:1'],
            'bonuses.*.max_donation' => ['nullable', 'numeric', 'gt:bonuses.*.min_donation'],
            'bonuses.*.quantity' => ['nullable', 'integer', 'min:1'],
            'bonuses.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Определяет статус для создаваемого проекта
     */
    public function getProjectStatus(): ProjectStatus
    {
        $requestedStatus = $this->input('status', 'new');

        // Мапим входящие статусы
        return match ($requestedStatus) {
            'draft' => ProjectStatus::Draft,
            'moderation' => ProjectStatus::Moderation,
            default => ProjectStatus::New,
        };
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'title.uk.required' => 'Українська назва проекту є обов\'язковою.',
            'user_type.required' => 'Тип користувача є обов\'язковим.',
            'art_category.required' => 'Категорія мистецтва є обов\'язковою.',
            'currency.required' => 'Валюта є обов\'язковою.',
            'budget_goal.required' => 'Цільовий бюджет є обов\'язковим.',
            'budget_goal.min' => 'Мінімальний бюджет - 100 одиниць.',
        ];
    }
}
