<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\ImageOrBase64Rule;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['required', new ImageOrBase64Rule(5120)], // 5MB, файл або Base64
            'website' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:20'],
            'description' => ['required', 'string', 'max:5000'],
            'specialization' => ['required', 'string', 'max:255'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required_with:members', 'integer', 'exists:users,id'],
        ];
    }
}
