<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', 'max:10'],
            'key'    => ['sometimes', 'string', 'max:255'],
            'value'  => ['sometimes', 'string'],
            'group'  => ['sometimes', 'string', 'max:100'],
            'tags'   => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }
}
