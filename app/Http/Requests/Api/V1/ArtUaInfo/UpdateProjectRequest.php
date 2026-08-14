<?php

namespace App\Http\Requests\Api\V1\ArtUaInfo;

use App\Enums\Currency;
use App\Enums\ProjectSource;
use App\Enums\UserType;
use App\Http\Requests\Api\V1\Concerns\NormalizesProjectUkrainianFields;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Request для редагування вже створеного проєкту з візарда art-ua-info.
 * Редагування вмісту саме по собі статус не змінює. Два дозволені
 * статус-переходи: status=draft (зняти опублікований проєкт з публічної
 * сторінки й повернути в чернетку) та status=moderation (опублікувати
 * чернетку — так само, як create-флоу, одразу завершує проєкт, бо цей
 * візард не має окремої модерації бюджету).
 */
class UpdateProjectRequest extends FormRequest
{
    use NormalizesProjectUkrainianFields { prepareForValidation as normalizeProjectUkrainianFields; }

    public function authorize(): bool
    {
        /** @var Project|null $project */
        $project = $this->route('project');
        $user = $this->user();

        if ($user === null || $project?->source !== ProjectSource::ArtUaInfo) {
            return false;
        }

        if ($project->user_id === $user->id) {
            return true;
        }

        // Командний проєкт може редагувати будь-який учасник команди, на яку він
        // підписаний (team_id), а не лише той, хто його фізично створив.
        return $project->team_id !== null
            && $user->teams()->where('teams.id', $project->team_id)->exists();
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
     * Переприв'язати командний проєкт на іншого власника (себе особисто,
     * юрособу чи іншу команду) може лише власник поточної команди — рядовий
     * учасник команди має право редагувати вміст проєкту, але не власника.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Project|null $project */
            $project = $this->route('project');

            if (! $project || $project->team_id === null) {
                return;
            }

            $keepsSameTeam = $this->input('user_type') === UserType::Team->value
                && (int) $this->input('team_id') === $project->team_id;

            if (! $keepsSameTeam && ! $project->team->isOwnedBy($this->user())) {
                $validator->errors()->add('user_type', 'Змінити власника проєкту може лише власник команди.');
            }
        });
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['draft', 'moderation'])],

            'user_type' => ['required', Rule::enum(UserType::class)],
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

            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],

            'cover' => ['nullable'],

            'art_category' => ['required', 'string', 'max:100'],
            'art_subcategory' => ['nullable', 'string', 'max:100'],

            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:100'],

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
