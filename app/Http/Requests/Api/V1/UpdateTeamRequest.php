<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\ImageOrBase64Rule;
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
            'name' => ['required', 'array'],
            'name.uk' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'avatar' => ['required', new ImageOrBase64Rule(5120)], // 5MB, файл або Base64
            'website' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'array'],
            'country.uk' => ['required', 'string', 'max:255'],
            'country.en' => ['required', 'string', 'max:255'],
            'city' => ['required', 'array'],
            'city.uk' => ['required', 'string', 'max:255'],
            'city.en' => ['required', 'string', 'max:255'],
            'region' => ['required', 'array'],
            'region.uk' => ['required', 'string', 'max:255'],
            'region.en' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'array'],
            'zip.uk' => ['required', 'string', 'max:20'],
            'zip.en' => ['required', 'string', 'max:20'],
            'description' => ['required', 'array'],
            'description.uk' => ['required', 'string', 'max:5000'],
            'description.en' => ['required', 'string', 'max:5000'],
            'specialization' => ['required', 'array'],
            'specialization.uk' => ['required', 'string', 'max:255'],
            'specialization.en' => ['required', 'string', 'max:255'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required_with:members', 'integer', 'exists:users,id'],
        ];
    }
}
