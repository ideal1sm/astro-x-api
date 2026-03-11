<?php

namespace App\Http\Api\Requests;

class CreateUserAddressRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:50'],
            'country'     => ['required', 'string', 'max:100'],
            'city'        => ['required', 'string', 'max:100'],
            'street'      => ['required', 'string', 'max:255'],
            'apartment'   => ['nullable', 'string', 'max:50'],
            'postal_code' => ['required', 'string', 'max:20'],
            'is_default'  => ['nullable', 'boolean'],
        ];
    }
}
