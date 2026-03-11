<?php

namespace App\Http\Api\Requests;

class UpdateUserAddressRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'country'     => ['sometimes', 'required', 'string', 'max:100'],
            'city'        => ['sometimes', 'required', 'string', 'max:100'],
            'street'      => ['sometimes', 'required', 'string', 'max:255'],
            'apartment'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'is_default'  => ['sometimes', 'boolean'],
        ];
    }
}
