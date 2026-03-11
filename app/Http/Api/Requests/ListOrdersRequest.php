<?php

namespace App\Http\Api\Requests;

use App\Enums\OrderStatus;

class ListOrdersRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $statusValues = implode(',', OrderStatus::values());

        return [
            'page'   => ['sometimes', 'integer', 'min:1'],
            'limit'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', "in:{$statusValues}"],
            'sort'   => ['sometimes', 'string', 'in:created_at,-created_at'],
        ];
    }
}
