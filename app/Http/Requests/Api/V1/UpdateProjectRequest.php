<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ArtCategory;
use App\Enums\Currency;
use App\Enums\UserType;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user() !== null
            && $project instanceof Project
            && $project->user_id === $this->user()->id
            && $project->isEditable();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Основні поля
            'user_type' => ['sometimes', Rule::enum(UserType::class)],

            'title' => ['sometimes', 'array'],
            'title.uk' => ['required_with:title', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            'short_description' => ['nullable', 'array'],
            'short_description.uk' => ['nullable', 'string', 'max:1000'],
            'short_description.en' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable', 'image', 'max:15360'], // 15MB

            // Категорія
            'art_category' => ['sometimes', Rule::enum(ArtCategory::class)],
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

            // Фінальний результат (для завершених)
            'final_result' => ['nullable', 'array'],
            'final_result.type' => ['required_with:final_result', 'in:image,gallery,video,link'],
            'final_result.url' => ['nullable', 'string', 'max:500'],
            'final_result.urls' => ['nullable', 'array', 'max:10'],
            'final_result.urls.*' => ['string', 'max:500'],
            'final_result.description' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.uk.required_with' => 'Назва проєкту українською є обов\'язковою',
            'budget_goal.min' => 'Мінімальна ціль збору — 100',
            'cover.max' => 'Максимальний розмір обкладинки — 15 МБ',
        ];
    }
}
