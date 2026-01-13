<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Project;
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
 */
class UpdatePublishedProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user() !== null
            && $project instanceof Project
            && $project->user_id === $this->user()->id
            && $project->isPartiallyEditable();
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
            'cover' => ['nullable', 'image', 'max:15360'], // 15MB

            // Теги
            'tags' => ['sometimes', 'array'],
            'tags.uk' => ['nullable', 'string', 'max:500'],
            'tags.en' => ['nullable', 'string', 'max:500'],

            // Додаткова інформація
            'additional_info' => ['sometimes', 'array'],
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
            'title.uk.required_with' => 'Назва проєкту українською є обов\'язковою',
            'cover.max' => 'Максимальний розмір обкладинки — 15 МБ',
        ];
    }
}
