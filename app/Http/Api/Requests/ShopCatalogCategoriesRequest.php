<?php

namespace App\Http\Api\Requests;

class ShopCatalogCategoriesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'  => ['integer', 'min:1'],
            'limit' => ['integer', 'min:1', 'max:100'],
            'sort'  => ['string', 'in:name,-name,created_at,-created_at'],
        ];
    }
}
