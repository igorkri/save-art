<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array'],
            'name.uk' => ['sometimes', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable'], // файл або Base64
            'website' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'array'],
            'country.uk' => ['nullable', 'string', 'max:255'],
            'country.en' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'array'],
            'city.uk' => ['nullable', 'string', 'max:255'],
            'city.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.uk' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required_with:members', 'integer', 'exists:users,id'],
        ];
    }
}
