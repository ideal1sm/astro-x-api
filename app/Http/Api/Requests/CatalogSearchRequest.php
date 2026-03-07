<?php

namespace App\Http\Api\Requests;

class CatalogSearchRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'     => ['required', 'string', 'min:2', 'max:200'],
            'type'  => ['string', 'in:products,categories,all'],
            'page'  => ['integer', 'min:1'],
            'limit' => ['integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Поисковый запрос обязателен.',
            'q.min'      => 'Поисковый запрос должен содержать минимум 2 символа.',
            'type.in'    => 'Допустимые значения type: products, categories, all.',
        ];
    }
}
