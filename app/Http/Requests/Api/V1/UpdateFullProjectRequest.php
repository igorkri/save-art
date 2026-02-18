<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Currency;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Models\ArtCategory;
use App\Models\Project;
use App\Rules\ImageOrBase64Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для оновлення повного проекту з етапами та бонусами в одному запиті
 */
class UpdateFullProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Project|null $project */
        $project = $this->route('project');

        if (! $project) {
            return false;
        }

        // Перевіряємо, що користувач є власником проєкту
        if ($this->user() === null || $project->user_id !== $this->user()->id) {
            return false;
        }

        // Повне оновлення доступне тільки для чернеток та відхилених
        return in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Rejected]);
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
            'user_type' => ['sometimes', Rule::enum(UserType::class)],

            'title' => ['sometimes', 'array'],
            'title.uk' => ['required_with:title', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            'short_description' => ['nullable', 'array'],
            'short_description.uk' => ['nullable', 'string', 'max:1000'],
            'short_description.en' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable', new ImageOrBase64Rule(15360)], // 15MB, підтримує файл, Base64, URL

            // Категорія (slug з БД)
            'art_category' => ['sometimes', 'string', Rule::in(ArtCategory::whereNull('parent_id')->pluck('slug')->all())],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array'],
            'tags.uk' => ['nullable', 'string', 'max:500'],
            'tags.en' => ['nullable', 'string', 'max:500'],

            // Бюджет
            'currency' => ['sometimes', Rule::enum(Currency::class)],
            'budget_goal' => ['sometimes', 'numeric', 'min:100'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            // Статті бюджету
            'budget_items' => ['nullable', 'array'],
            'budget_items.*.name' => ['required_with:budget_items', 'array'],
            'budget_items.*.name.uk' => ['required_with:budget_items', 'string', 'max:255'],
            'budget_items.*.name.en' => ['nullable', 'string', 'max:255'],
            'budget_items.*.amount' => ['required_with:budget_items', 'numeric', 'min:0'],

            // Характеристики
            'characteristics' => ['nullable', 'array'],
            'characteristics.*.name' => ['required_with:characteristics', 'array'],
            'characteristics.*.name.uk' => ['required_with:characteristics', 'string', 'max:255'],
            'characteristics.*.name.en' => ['nullable', 'string', 'max:255'],
            'characteristics.*.value' => ['required_with:characteristics', 'array'],
            'characteristics.*.value.uk' => ['required_with:characteristics', 'string', 'max:500'],
            'characteristics.*.value.en' => ['nullable', 'string', 'max:500'],

            // Додаткова інформація
            'additional_info' => ['nullable', 'array'],
            'additional_info.uk' => ['nullable', 'string', 'max:10000'],
            'additional_info.en' => ['nullable', 'string', 'max:10000'],

            // Контент-блоки
            'content_blocks' => ['nullable', 'array', 'max:50'],
            'content_blocks.*.type' => ['required_with:content_blocks', 'string', 'in:heading,paragraph,image'],
            'content_blocks.*.heading_level' => ['nullable', 'string', 'in:h2,h3,h4,h5,h6'],
            'content_blocks.*.heading_text' => ['nullable', 'array'],
            'content_blocks.*.heading_text.uk' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.heading_text.en' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.paragraph_text' => ['nullable', 'array'],
            'content_blocks.*.paragraph_text.uk' => ['nullable', 'string', 'max:10000'],
            'content_blocks.*.paragraph_text.en' => ['nullable', 'string', 'max:10000'],
            'content_blocks.*.image' => ['nullable', new ImageOrBase64Rule(15360)],
            'content_blocks.*.image_alt' => ['nullable', 'array'],
            'content_blocks.*.image_alt.uk' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.image_alt.en' => ['nullable', 'string', 'max:255'],
            'content_blocks.*.image_caption' => ['nullable', 'array'],
            'content_blocks.*.image_caption.uk' => ['nullable', 'string', 'max:500'],
            'content_blocks.*.image_caption.en' => ['nullable', 'string', 'max:500'],

            // ========== Етапи проекту ==========
            'stages' => ['nullable', 'array', 'max:20'],
            'stages.*.id' => ['nullable', 'integer', 'exists:project_stages,id'],
            'stages.*.title' => ['required', 'array'],
            'stages.*.title.uk' => ['required', 'string', 'max:255'],
            'stages.*.title.en' => ['nullable', 'string', 'max:255'],
            'stages.*.description' => ['nullable', 'array'],
            'stages.*.description.uk' => ['nullable', 'string', 'max:2000'],
            'stages.*.description.en' => ['nullable', 'string', 'max:2000'],
            'stages.*.days_planned' => ['nullable', 'integer', 'min:1'],
            'stages.*.budget_planned' => ['nullable', 'numeric', 'min:0'],
            'stages.*.order' => ['nullable', 'integer', 'min:0'],

            // ========== Бонуси для меценатів ==========
            'bonuses' => ['nullable', 'array', 'max:20'],
            'bonuses.*.id' => ['nullable', 'integer', 'exists:project_bonuses,id'],
            'bonuses.*.title' => ['required', 'array'],
            'bonuses.*.title.uk' => ['required', 'string', 'max:255'],
            'bonuses.*.title.en' => ['nullable', 'string', 'max:255'],
            'bonuses.*.description' => ['nullable', 'array'],
            'bonuses.*.description.uk' => ['nullable', 'string', 'max:2000'],
            'bonuses.*.description.en' => ['nullable', 'string', 'max:2000'],
            'bonuses.*.min_donation' => ['required', 'numeric', 'min:10'],
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
            'title.uk.required_with' => 'Назва проєкту українською є обов\'язковою',
            'budget_goal.min' => 'Мінімальна ціль збору — 100',
            'cover.max' => 'Максимальний розмір обкладинки — 15 МБ',

            // Контент-блоки
            'content_blocks.max' => 'Максимум 50 контент-блоків',
            'content_blocks.*.type.required_with' => 'Тип контент-блоку є обов\'язковим',
            'content_blocks.*.type.in' => 'Тип контент-блоку має бути: heading, paragraph або image',

            // Етапи
            'stages.max' => 'Максимум 20 етапів',
            'stages.*.title.uk.required' => 'Назва етапу українською є обов\'язковою',
            'stages.*.id.exists' => 'Етап не знайдено',

            // Бонуси
            'bonuses.max' => 'Максимум 20 бонусів',
            'bonuses.*.title.uk.required' => 'Назва бонусу українською є обов\'язковою',
            'bonuses.*.min_donation.required' => 'Мінімальна сума підтримки для бонусу є обов\'язковою',
            'bonuses.*.min_donation.min' => 'Мінімальна сума підтримки — 10',
            'bonuses.*.id.exists' => 'Бонус не знайдено',
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
            'stages.*.title.uk' => 'назва етапу',
            'stages.*.description.uk' => 'опис етапу',
            'bonuses.*.title.uk' => 'назва бонусу',
            'bonuses.*.description.uk' => 'опис бонусу',
            'bonuses.*.min_donation' => 'мінімальна сума підтримки',
        ];
    }
}
