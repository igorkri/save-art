<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProjectStatus;
use App\Models\ArtCategory;
use App\Models\Project;
use App\Rules\ImageOrBase64Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Request для часткового оновлення опублікованого проєкту (03.4.2.2)
 *
 * Дозволяє редагувати:
 * - Назву (title), короткий опис (short_description), теги (tags)
 * - Додаткову інформацію (additional_info), обкладинку (cover), контент-блоки (content_blocks)
 * - Категорію (art_category/art_subcategory)
 * - Бюджет (budget_goal, budget_items) — для 'announced' лише збільшення budget_goal
 *   (донати вже прийняті на початкову ціль), для 'in_progress'/'paused' без обмежень
 *
 * Для 'completed'/'sold' (canEditAdditionalContentOnly()) дозволено редагувати лише
 * content_blocks, additional_info, cover та tags — назву/категорію/бюджет змінити вже не можна
 * (withValidator() нижче явно відхиляє ці поля для цих статусів).
 */
class UpdatePublishedProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        // Перевірка, чи користувач автентифікований
        if ($this->user() === null) {
            abort(401, 'User not authenticated');
        }

        // Перевірка, чи проект знайдено
        if (! $project instanceof Project) {
            abort(404, 'Project not found');
        }

        // Перевірка, чи проект належить користувачу
        if ($project->user_id !== $this->user()->id) {
            abort(403, 'You do not own this project');
        }

        // Перевірка, чи проект можна редагувати частково. Завершені/продані сюди теж потрапляють,
        // але лише для контенту (content_blocks/additional_info/cover) — див. withValidator().
        if (! $project->isPartiallyEditable() && ! $project->canEditAdditionalContentOnly()) {
            abort(403, "Project with status '{$project->status->value}' cannot be partially edited. Only projects with status 'announced', 'in_progress', 'paused', 'completed', or 'sold' can be partially edited.");
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Назва
            'title' => ['sometimes', 'array'],
            'title.uk' => ['required_with:title', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],

            // Короткий опис
            'short_description' => ['sometimes', 'array'],
            'short_description.uk' => ['nullable', 'string', 'max:1000'],
            'short_description.en' => ['nullable', 'string', 'max:1000'],

            // Обкладинка
            'cover' => ['nullable', new ImageOrBase64Rule(15360)], // 15MB, підтримує файл, Base64, URL

            // Теги
            'tags' => ['sometimes', 'array'],
            'tags.uk' => ['nullable', 'string', 'max:500'],
            'tags.en' => ['nullable', 'string', 'max:500'],

            // Категорія (slug з БД)
            'art_category' => ['sometimes', 'nullable', 'string', Rule::in(ArtCategory::whereNull('parent_id')->pluck('slug')->all())],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            // Бюджет: goal для 'announced' обмежений в withValidator() (лише збільшення)
            'budget_goal' => ['sometimes', 'numeric', 'min:100'],
            'budget_items' => ['sometimes', 'nullable', 'array'],
            'budget_items.*.name' => ['required_with:budget_items', 'array'],
            'budget_items.*.name.uk' => ['required_with:budget_items', 'string', 'max:255'],
            'budget_items.*.name.en' => ['nullable', 'string', 'max:255'],
            'budget_items.*.amount' => ['required_with:budget_items', 'numeric', 'min:0'],

            // Додаткова інформація
            'additional_info' => ['sometimes', 'array'],
            'additional_info.uk' => ['nullable', 'string', 'max:10000'],
            'additional_info.en' => ['nullable', 'string', 'max:10000'],

            // Контент-блоки
            'content_blocks' => ['nullable', 'array', 'max:50'],
            'content_blocks.*.type' => ['required_with:content_blocks', 'string', 'in:heading,paragraph,image,link'],
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
            'content_blocks.*.url' => ['nullable', 'string', 'max:500', 'url'],

            // Характеристики (прив'язані до категорії)
            'parameters' => ['nullable', 'array'],
            'parameters.*.parameter_id' => ['required_with:parameters', 'integer', 'exists:parameters,id'],
            'parameters.*.parameter_value_id' => ['nullable', 'integer', 'exists:parameter_values,id'],
            'parameters.*.custom_value' => ['nullable', 'array'],
            'parameters.*.custom_value.uk' => ['nullable', 'string', 'max:255'],
            'parameters.*.custom_value.en' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Для 'announced' бюджет можна лише збільшувати (донати вже прийняті під початкову ціль,
     * зменшення дозволене тільки для 'in_progress'/'paused' — див. project-lifecycle-flow.md)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $project = $this->route('project');

            if (! $project instanceof Project) {
                return;
            }

            // Завершені/продані: контент (content_blocks/additional_info/cover/tags) — назву,
            // категорію та бюджет для них міняти не можна навіть через цей "частковий" ендпоінт.
            if ($project->canEditAdditionalContentOnly()) {
                $forbiddenFields = array_intersect(
                    array_keys($this->all()),
                    ['title', 'short_description', 'art_category', 'art_subcategory', 'budget_goal', 'budget_items', 'parameters']
                );
                foreach ($forbiddenFields as $field) {
                    $validator->errors()->add($field, "Поле '{$field}' не можна редагувати для завершеного/проданого проєкту. Доступні лише content_blocks, additional_info, cover та tags.");
                }

                return;
            }

            if (! $this->has('budget_goal')) {
                return;
            }

            if ($project->status === ProjectStatus::Announced && (float) $this->input('budget_goal') < (float) $project->budget_goal) {
                $validator->errors()->add('budget_goal', 'Для оголошеного проєкту бюджет можна лише збільшувати.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.uk.required_with' => 'Назва проєкту українською є обов\'язковою',
            'cover.max' => 'Максимальний розмір обкладинки — 15 МБ',
            'budget_goal.min' => 'Мінімальна ціль збору — 100',

            // Категорія
            'art_category.in' => 'Невірна категорія мистецтва',

            // Контент-блоки
            'content_blocks.max' => 'Максимум 50 контент-блоків',
            'content_blocks.*.type.required_with' => 'Тип контент-блоку є обов\'язковим',
            'content_blocks.*.type.in' => 'Тип контент-блоку має бути: heading, paragraph, image або link',
        ];
    }
}
