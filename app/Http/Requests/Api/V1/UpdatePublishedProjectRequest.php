<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Project;
use App\Rules\ImageOrBase64Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request для часткового оновлення опублікованого проєкту (03.4.2.2)
 *
 * Дозволяє редагувати лише:
 * - Назву (title)
 * - Короткий опис (short_description)
 * - Теги (tags)
 * - Додаткову інформацію (additional_info)
 * - Обкладинку (cover)
 * - Контент-блоки (content_blocks)
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

        // Перевірка, чи проект можна редагувати частково
        if (! $project->isPartiallyEditable()) {
            abort(403, "Project with status '{$project->status->value}' cannot be partially edited. Only projects with status 'announced', 'in_progress', or 'paused' can be partially edited.");
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

            // Додаткова інформація
            'additional_info' => ['sometimes', 'array'],
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.uk.required_with' => 'Назва проєкту українською є обов\'язковою',
            'cover.max' => 'Максимальний розмір обкладинки — 15 МБ',

            // Контент-блоки
            'content_blocks.max' => 'Максимум 50 контент-блоків',
            'content_blocks.*.type.required_with' => 'Тип контент-блоку є обов\'язковим',
            'content_blocks.*.type.in' => 'Тип контент-блоку має бути: heading, paragraph або image',
        ];
    }
}
