<?php

namespace App\Http\Requests\Api;

use App\Enums\SignService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sign_service' => [
                'required',
                'string',
                Rule::enum(SignService::class),
            ],
            'signature_base64' => [
                'required',
                'string',
                'min:10',
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
            'sign_service.required' => __('contracts.validation.sign_service_required'),
            'sign_service.enum' => __('contracts.validation.sign_service_invalid'),
            'signature_base64.required' => __('contracts.validation.signature_required'),
            'signature_base64.min' => __('contracts.validation.signature_too_short'),
        ];
    }
}
