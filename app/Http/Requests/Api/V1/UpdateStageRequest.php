<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\StageStatus;
use App\Http\Requests\Api\V1\Concerns\NormalizesProjectUkrainianFields;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStageRequest extends FormRequest
{
    use NormalizesProjectUkrainianFields;

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
        return $this->user() && $project->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(StageStatus::class)],
            'days_planned' => ['nullable', 'integer', 'min:1'],
            'budget_planned' => ['nullable', 'numeric', 'min:0'],
            'budget_actual' => ['nullable', 'numeric', 'min:0'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required_with' => 'Назва етапу є обов\'язковою',
        ];
    }
}
