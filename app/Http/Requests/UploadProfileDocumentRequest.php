<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProfileDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Пользователь может загружать свои документы
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // Максимум 10MB
                'mimes:pdf,doc,docx,jpg,jpeg,png,zip,rar',
            ],
            'service' => [
                'sometimes',
                'string',
                'in:diia,vchasno,iit',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Файл обов\'язковий для завантаження.',
            'file.file' => 'Завантажений файл є недійсним.',
            'file.max' => 'Розмір файлу не повинен перевищувати 10 МБ.',
            'file.mimes' => 'Файл повинен бути одного з типів: PDF, DOC, DOCX, JPG, JPEG, PNG, ZIP, RAR.',
            'service.in' => 'Невірний сервіс. Дозволені: diia, vchasno, iit.',
        ];
    }
}
