<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ArtCatalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArtCatalogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var ArtCatalog|null $catalog */
        $catalog = $this->route('catalog');

        return $this->user() !== null
            && $catalog?->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable'], // файл або Base64, необов'язково при редагуванні
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'art_category' => ['required', 'string', 'max:100'],
            'art_subcategory' => ['nullable', 'string', 'max:100'],
            'published_at' => ['nullable', 'date'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Кастомні повідомлення про помилки
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Введіть назву каталогу українською.',
            'art_category.required' => 'Оберіть галузь мистецтва.',
        ];
    }
}
