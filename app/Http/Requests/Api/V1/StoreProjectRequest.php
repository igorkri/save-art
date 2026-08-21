<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\Currency;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Http\Requests\Api\V1\Concerns\NormalizesProjectUkrainianFields;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для створення проекту (новий/чернетка) з динамічними правилами валідації
 */
class StoreProjectRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $status = $this->input('status', 'new');
        $isDraft = in_array($status, ['new', 'draft']);

        return [
            // ========== Обов'язкові поля для всіх типів ==========
            // Подання на модерацію виконується окремим /submit, щоб перехід
            // завжди проходив через ProjectWorkflowService.
            'status' => ['nullable', 'string', Rule::in([
                ProjectStatus::New->value,
                ProjectStatus::Draft->value,
            ])],
            'local_id' => ['nullable', 'string', 'max:100'],

            // ========== Основні поля проекту ==========
            'user_type' => [$isDraft ? 'nullable' : 'required', Rule::enum(UserType::class)],

            'title' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable'], // Может быть файл или Base64

            // Категорія - обязательно для публикации, опционально для черновика
            'art_category' => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:100'],

            // ========== Бюджет та валюта ==========
            'currency' => [$isDraft ? 'nullable' : 'required', Rule::enum(Currency::class)],
            'budget_goal' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:100', 'max:999999999'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],

            // ========== Структуровані дані ==========
            'budget_items' => ['nullable', 'array', 'max:50'],
            'budget_items.*.name' => ['sometimes', 'string', 'max:255'],
            'budget_items.*.amount' => ['sometimes', 'numeric', 'min:0'],

            'additional_info' => ['nullable', 'array'],
            'additional_info.uk' => ['nullable', 'string', 'max:5000'],
            'additional_info.en' => ['nullable', 'string', 'max:5000'],

            // ========== Контент-блоки ==========
            'content_blocks' => ['nullable', 'array', 'max:50'],
            'content_blocks.*.type' => ['sometimes', 'string', 'in:heading,paragraph,image,link'],
            'content_blocks.*.heading_level' => ['sometimes', 'string', 'in:h1,h2,h3,h4,h5,h6'],
            'content_blocks.*.heading_text' => ['sometimes', 'string', 'max:255'],
            'content_blocks.*.paragraph_text' => ['sometimes', 'string', 'max:10000'],
            'content_blocks.*.image' => ['sometimes', 'string'],
            'content_blocks.*.image_alt' => ['sometimes', 'string', 'max:255'],
            'content_blocks.*.url' => ['nullable', 'string', 'max:500', 'url'],

            // ========== Етапи (опціонально для чернеток) ==========
            'stages' => ['nullable', 'array'],
            'stages.*.title' => ['sometimes', 'string', 'max:255'],
            'stages.*.description' => ['sometimes', 'string', 'max:2000'],
            'stages.*.days_planned' => ['sometimes', 'integer', 'min:1'],
            'stages.*.budget_planned' => ['sometimes', 'numeric', 'min:0'],
            'stages.*.order' => ['nullable', 'integer', 'min:0'],

            // ========== Бонуси (опціонально для чернеток) ==========
            'bonuses' => ['nullable', 'array'],
            'bonuses.*.title' => ['sometimes', 'string', 'max:255'],
            'bonuses.*.description' => ['sometimes', 'string', 'max:2000'],
            'bonuses.*.min_donation' => ['sometimes', 'numeric', 'min:1'],
            'bonuses.*.max_donation' => ['nullable', 'numeric', 'gt:bonuses.*.min_donation'],
            'bonuses.*.quantity' => ['nullable', 'integer', 'min:1'],
            'bonuses.*.order' => ['nullable', 'integer', 'min:0'],

            // ========== Характеристики (опціонально для чернеток) ==========
            'parameters' => ['nullable', 'array'],
            'parameters.*.parameter_id' => ['required_with:parameters', 'integer', 'exists:parameters,id'],
            'parameters.*.parameter_value_id' => ['nullable', 'integer', 'exists:parameter_values,id'],
            'parameters.*.custom_value' => ['nullable', 'string', 'max:255'],
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
            default => ProjectStatus::New,
        };
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Назва проекту є обов\'язковою.',
            'user_type.required' => 'Тип користувача є обов\'язковим.',
            'art_category.required' => 'Категорія мистецтва є обов\'язковою.',
            'currency.required' => 'Валюта є обов\'язковою.',
            'budget_goal.required' => 'Цільовий бюджет є обов\'язковим.',
            'budget_goal.min' => 'Мінімальний бюджет - 100 одиниць.',
        ];
    }
}
