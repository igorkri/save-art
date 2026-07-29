<?php

namespace App\Http\Requests\Api\V1\ArtUaInfo;

use App\Enums\Currency;
use App\Enums\ProjectSource;
use App\Enums\UserType;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для редагування вже створеного проєкту з візарда art-ua-info.
 * Статус проєкту (completed/approved) редагування не змінює — тут лише вміст.
 */
class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project|null $project */
        $project = $this->route('project');

        return $this->user() !== null
            && $project?->user_id === $this->user()->id
            && $project?->source === ProjectSource::ArtUaInfo;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_type' => ['required', Rule::enum(UserType::class)],

            'title' => ['required', 'array'],
            'title.uk' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],

            'short_description' => ['nullable', 'array'],
            'short_description.uk' => ['nullable', 'string', 'max:1000'],
            'short_description.en' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable'],

            'art_category' => ['required', 'string', 'max:100'],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array'],
            'tags.uk' => ['nullable', 'array', 'max:30'],
            'tags.uk.*' => ['string', 'max:100'],
            'tags.en' => ['nullable', 'array', 'max:30'],
            'tags.en.*' => ['string', 'max:100'],

            'currency' => ['nullable', Rule::enum(Currency::class)],

            // Робота (галерея зображень + посилання на відео), яку показуємо в прев'ю проєкту.
            'final_result' => ['required', 'array', 'max:50'],
            'final_result.*.type' => ['sometimes', 'string', 'in:image,link'],
            'final_result.*.image' => ['sometimes', 'string'],
            'final_result.*.url' => ['nullable', 'string', 'max:500', 'url'],

            // Додаткова інформація про проєкт (блоки: заголовок/текст/зображення/посилання).
            'content_blocks' => ['nullable', 'array', 'max:50'],
            'content_blocks.*.type' => ['sometimes', 'string', 'in:heading,paragraph,image,link'],
            'content_blocks.*.heading_level' => ['sometimes', 'string', 'in:h1,h2,h3,h4,h5,h6'],
            'content_blocks.*.heading_text' => ['sometimes', 'array'],
            'content_blocks.*.paragraph_text' => ['sometimes', 'array'],
            'content_blocks.*.image' => ['sometimes', 'string'],
            'content_blocks.*.image_alt' => ['sometimes', 'array'],
            'content_blocks.*.url' => ['nullable', 'string', 'max:500', 'url'],

            // Проєкт уже продано на іншій платформі (art-ua.com чи іншій).
            'sold_externally' => ['nullable', 'boolean'],

            'parameters' => ['nullable', 'array'],
            'parameters.*.parameter_id' => ['required_with:parameters', 'integer', 'exists:parameters,id'],
            'parameters.*.parameter_value_id' => ['nullable', 'integer', 'exists:parameter_values,id'],
            'parameters.*.custom_value' => ['nullable', 'array'],
            'parameters.*.custom_value.uk' => ['nullable', 'string', 'max:255'],
            'parameters.*.custom_value.en' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_type.required' => 'Оберіть власника проєкту.',
            'title.uk.required' => 'Введіть назву проєкту українською.',
            'title.en.required' => 'Введіть назву проєкту англійською.',
            'art_category.required' => 'Оберіть галузь мистецтва.',
            'final_result.required' => 'Додайте роботу.',
        ];
    }
}
