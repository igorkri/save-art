<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Currency;
use App\Enums\UserType;
use App\Http\Requests\Api\V1\Concerns\NormalizesProjectUkrainianFields;
use App\Models\ArtCategory;
use App\Rules\ImageOrBase64Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для створення повного проекту з етапами та бонусами в одному запиті
 */
class StoreFullProjectRequest extends FormRequest
{
    use NormalizesProjectUkrainianFields { prepareForValidation as normalizeProjectUkrainianFields; }

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
        $this->normalizeProjectUkrainianFields();
        $this->merge(['currency' => Currency::USD->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // ========== Основні поля проекту ==========
            'user_type' => ['required', Rule::enum(UserType::class)],

            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable', new ImageOrBase64Rule(15360)], // 15MB, підтримує файл, Base64, URL

            // Категорія (slug з БД)
            'art_category' => ['required', 'string', Rule::in(ArtCategory::whereNull('parent_id')->pluck('slug')->all())],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'string', 'max:500'],

            // Бюджет
            'currency' => ['required', Rule::enum(Currency::class)],
            'budget_goal' => ['required', 'numeric', 'min:100'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            // Статті бюджету
            'budget_items' => ['nullable', 'array'],
            'budget_items.*.name' => ['required_with:budget_items', 'string', 'max:255'],
            'budget_items.*.amount' => ['required_with:budget_items', 'numeric', 'min:0'],

            // Додаткова інформація
            'additional_info' => ['nullable', 'array'],
            'additional_info.uk' => ['nullable', 'string', 'max:10000'],
            'additional_info.en' => ['nullable', 'string', 'max:10000'],

            // Контент-блоки
            'content_blocks' => ['nullable', 'array', 'max:50'],
            'content_blocks.*.type' => ['required_with:content_blocks', 'string', 'in:heading,paragraph,image,link'],
            'content_blocks.*.heading_level' => ['nullable', 'string', 'in:h2,h3,h4,h5,h6'],
            'content_blocks.*.heading_text' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.paragraph_text' => ['nullable', 'string', 'max:10000'],
            'content_blocks.*.image' => ['nullable', new ImageOrBase64Rule(15360)],
            'content_blocks.*.image_alt' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.image_caption' => ['nullable', 'string', 'max:500'],
            'content_blocks.*.url' => ['nullable', 'string', 'max:500', 'url'],

            // ========== Етапи проекту ==========
            'stages' => ['nullable', 'array', 'max:20'],
            'stages.*.title' => ['required', 'string', 'max:255'],
            'stages.*.description' => ['nullable', 'string', 'max:2000'],
            'stages.*.days_planned' => ['nullable', 'integer', 'min:1'],
            'stages.*.budget_planned' => ['nullable', 'numeric', 'min:0'],
            'stages.*.order' => ['nullable', 'integer', 'min:0'],

            // ========== Бонуси для меценатів ==========
            'bonuses' => ['nullable', 'array', 'max:20'],
            'bonuses.*.title' => ['required', 'string', 'max:255'],
            'bonuses.*.description' => ['nullable', 'string', 'max:2000'],
            'bonuses.*.min_donation' => ['required', 'numeric', 'min:10'],
            'bonuses.*.max_donation' => ['nullable', 'numeric', 'gt:bonuses.*.min_donation'],
            'bonuses.*.quantity' => ['nullable', 'integer', 'min:1'],
            'bonuses.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Проект
            'title.required' => 'Назва проєкту є обов\'язковою',
            'art_category.required' => 'Оберіть галузь мистецтва',
            'budget_goal.required' => 'Вкажіть ціль збору',
            'budget_goal.min' => 'Мінімальна ціль збору — 100',
            'cover.max' => 'Максимальний розмір обкладинки — 15 МБ',

            // Контент-блоки
            'content_blocks.max' => 'Максимум 50 контент-блоків',
            'content_blocks.*.type.required_with' => 'Тип контент-блоку є обов\'язковим',
            'content_blocks.*.type.in' => 'Тип контент-блоку має бути: heading, paragraph, image або link',

            // Етапи
            'stages.max' => 'Максимум 20 етапів',
            'stages.*.title.required' => 'Назва етапу є обов\'язковою',

            // Бонуси
            'bonuses.max' => 'Максимум 20 бонусів',
            'bonuses.*.title.required' => 'Назва бонусу є обов\'язковою',
            'bonuses.*.min_donation.required' => 'Мінімальна сума підтримки для бонусу є обов\'язковою',
            'bonuses.*.min_donation.min' => 'Мінімальна сума підтримки — 10',
            'bonuses.*.max_donation.gt' => 'Максимальна сума підтримки має бути більшою за мінімальну',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stages.*.title' => 'назва етапу',
            'stages.*.description' => 'опис етапу',
            'bonuses.*.title' => 'назва бонусу',
            'bonuses.*.description' => 'опис бонусу',
            'bonuses.*.min_donation' => 'мінімальна сума підтримки',
            'bonuses.*.max_donation' => 'максимальна сума підтримки',
        ];
    }
}
