<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\NormalizesProjectUkrainianFields;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class CreateBonusRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'min_donation' => ['required', 'numeric', 'min:10'],
            'max_donation' => ['nullable', 'numeric', 'gt:min_donation'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Назва бонусу є обов\'язковою',
            'min_donation.required' => 'Мінімальна сума підтримки є обов\'язковою',
            'min_donation.min' => 'Мінімальна сума підтримки — 10',
            'max_donation.gt' => 'Максимальна сума має бути більшою за мінімальну',
        ];
    }
}
