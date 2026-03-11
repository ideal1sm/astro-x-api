<?php

namespace App\Http\Api\Requests;

use Illuminate\Support\Facades\Auth;

class CreateOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $rules = [
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'delivery_address_id'  => ['nullable', 'integer'],
            'notes'                => ['nullable', 'string', 'max:2000'],
        ];

        // Если адрес указан — он должен принадлежать текущему пользователю
        if ($this->input('delivery_address_id')) {
            $userId = Auth::id();
            $rules['delivery_address_id'][] = "exists:user_addresses,id,user_id,{$userId}";
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'Список позиций обязателен.',
            'items.min'                   => 'Заказ должен содержать хотя бы одну позицию.',
            'items.*.product_id.exists'   => 'Товар не найден.',
            'items.*.quantity.min'        => 'Количество должно быть не менее 1.',
            'delivery_address_id.exists'  => 'Адрес доставки не найден или вам не принадлежит.',
        ];
    }
}
