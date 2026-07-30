<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
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
            'title' => ['sometimes', 'array'],
            'title.uk' => ['sometimes', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.uk' => ['nullable', 'string', 'max:5000'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable'], // файл або Base64
            'art_category' => ['required', 'string', 'max:100'],
            'art_subcategory' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_from' => ['nullable', 'boolean'],
            'currency' => ['nullable', 'string', 'in:UAH,USD,EUR'],
            'options' => ['nullable', 'array', 'max:50'],
            'options.*.name' => ['required_with:options', 'array'],
            'options.*.name.uk' => ['required_with:options', 'string', 'max:255'],
            'options.*.name.en' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Кастомні повідомлення про помилки
     */
    public function messages(): array
    {
        return [
            'art_category.required' => 'Оберіть галузь мистецтва.',
        ];
    }
}
