<?php

namespace App\Http\Api\Requests\Auth;

use App\Http\Api\Requests\ApiFormRequest;

class ConfirmEmailRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }
}
