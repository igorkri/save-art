<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtCatalogRequest extends FormRequest
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
            'title' => ['required', 'array'],
            'title.uk' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'image' => ['required'], // файл або Base64
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'art_category_id' => ['nullable', 'integer', 'exists:art_categories,id'],
            'published_at' => ['nullable', 'date'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
