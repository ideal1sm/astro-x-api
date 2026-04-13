<?php

namespace App\Http\Api\Requests;

class ShopCatalogSearchRequest extends ApiFormRequest
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
}
