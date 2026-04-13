<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale'      => ['required', 'string', 'max:10'],
            'key'         => [
                'required', 'string', 'max:255',
                Rule::unique('translations')->where('locale', $this->input('locale')),
            ],
            'value'       => ['required', 'string'],
            'group'       => ['sometimes', 'string', 'max:100'],
            'tags'        => ['sometimes', 'array'],
            'tags.*'      => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'locale.required' => 'A locale code is required (e.g. en, fr, es).',
            'key.required'    => 'A translation key is required.',
            'value.required'  => 'A translation value is required.',
        ];
    }
}
