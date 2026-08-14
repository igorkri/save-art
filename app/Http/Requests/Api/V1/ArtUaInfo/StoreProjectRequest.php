<?php

namespace App\Http\Requests\Api\V1\ArtUaInfo;

use App\Enums\Currency;
use App\Enums\ProjectStatus;
use App\Enums\UserType;
use App\Http\Requests\Api\V1\Concerns\NormalizesProjectUkrainianFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для створення проєкту з візарда art-ua-info. На відміну від
 * App\Http\Requests\Api\V1\StoreProjectRequest (save-art), цей візард не збирає
 * бюджет/етапи/бонуси/характеристики — тому їх нема в rules() зовсім (Laravel не
 * вимагає ключів, яких немає в правилах).
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $status = $this->input('status', 'new');
        $isDraft = in_array($status, ['new', 'draft']);

        return [
            'status' => ['nullable', 'string', Rule::enum(ProjectStatus::class)],
            'local_id' => ['nullable', 'string', 'max:100'],

            'user_type' => [$isDraft ? 'nullable' : 'required', Rule::enum(UserType::class)],
            'team_id' => [
                'nullable',
                'required_if:user_type,team',
                'integer',
                Rule::exists('teams', 'id'),
                function ($attribute, $value, $fail): void {
                    if ($value === null) {
                        return;
                    }
                    if (! $this->user()->teams()->where('teams.id', $value)->exists()) {
                        $fail('Ви не є учасником обраної команди.');
                    }
                },
            ],

            'title' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable'],

            'art_category' => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:100'],

            'currency' => ['nullable', Rule::enum(Currency::class)],

            // Робота (галерея зображень + посилання на відео), яку показуємо в прев'ю проєкту.
            'final_result' => [$isDraft ? 'nullable' : 'required', 'array', 'max:50'],
            'final_result.*.type' => ['sometimes', 'string', 'in:image,link'],
            'final_result.*.image' => ['sometimes', 'string'],
            'final_result.*.url' => ['nullable', 'string', 'max:500', 'url'],

            // Додаткова інформація про проєкт (блоки: заголовок/текст/зображення/посилання).
            'content_blocks' => ['nullable', 'array', 'max:50'],
            'content_blocks.*.type' => ['sometimes', 'string', 'in:heading,paragraph,image,link'],
            'content_blocks.*.heading_level' => ['sometimes', 'string', 'in:h1,h2,h3,h4,h5,h6'],
            'content_blocks.*.heading_text' => ['sometimes', 'string', 'max:255'],
            'content_blocks.*.paragraph_text' => ['sometimes', 'string', 'max:10000'],
            'content_blocks.*.image' => ['sometimes', 'string'],
            'content_blocks.*.image_alt' => ['sometimes', 'string', 'max:255'],
            'content_blocks.*.url' => ['nullable', 'string', 'max:500', 'url'],

            // Проєкт уже продано на іншій платформі (art-ua.com чи іншій).
            'sold_externally' => ['nullable', 'boolean'],

            'parameters' => ['nullable', 'array'],
            'parameters.*.parameter_id' => ['required_with:parameters', 'integer', 'exists:parameters,id'],
            'parameters.*.parameter_value_id' => ['nullable', 'integer', 'exists:parameter_values,id'],
            'parameters.*.custom_value' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Визначає статус для створюваного проєкту.
     * Візард art-ua-info не має модерації бюджету — тож будь-яке збереження,
     * окрім чернетки (new/draft), одразу завершує проєкт (Completed).
     */
    public function getProjectStatus(): ProjectStatus
    {
        $requestedStatus = $this->input('status', 'new');

        if (! in_array($requestedStatus, ['draft', 'new'], true) && $this->boolean('sold_externally')) {
            return ProjectStatus::Sold;
        }

        return match ($requestedStatus) {
            'draft' => ProjectStatus::Draft,
            'new' => ProjectStatus::New,
            default => ProjectStatus::Completed,
        };
    }

    /**
     * Чи зберігається проєкт як чернетка (не завершується автоматично)
     */
    public function isDraftSave(): bool
    {
        return in_array($this->input('status', 'new'), ['new', 'draft'], true);
    }

    /**
     * Кастомні повідомлення про помилки
     */
    public function messages(): array
    {
        return [
            'user_type.required' => 'Оберіть власника проєкту.',
            'title.required' => 'Введіть назву проєкту.',
            'art_category.required' => 'Оберіть галузь мистецтва.',
            'final_result.required' => 'Додайте роботу.',
        ];
    }
}
