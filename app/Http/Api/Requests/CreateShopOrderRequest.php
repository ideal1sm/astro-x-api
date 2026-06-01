<?php

namespace App\Http\Api\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

class CreateShopOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'customer_name'           => ['required', 'string', 'max:255'],
            'customer_phone'          => ['required', 'string', 'max:32', 'regex:/^\+?[0-9\s\-\(\)]{10,32}$/'],
            'customer_email'          => ['required', 'email:rfc', 'max:255'],
            'personal_data_consent'   => ['accepted'],
            'payment_method'          => ['required', Rule::enum(PaymentMethod::class)],
            'payment_return_url'      => ['required', 'url', 'max:2048'],
            'delivery_method'         => ['required', 'string', 'max:100'],
            'delivery_price'          => ['required', 'numeric', 'min:0'],
            'delivery_payload'        => ['required', 'array'],
            'recipient_name'          => ['required', 'string', 'max:255'],
            'recipient_phone'         => ['required', 'string', 'max:32', 'regex:/^\+?[0-9\s\-\(\)]{10,32}$/'],
            'delivery_city'           => ['required', 'string', 'max:255'],
            'delivery_address'        => ['nullable', 'string', 'max:1000', 'required_without:delivery_pickup_point'],
            'delivery_pickup_point'   => ['nullable', 'string', 'max:1000', 'required_without:delivery_address'],
            'delivery_comment'        => ['nullable', 'string', 'max:2000'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.shop_product_id' => ['required', 'integer', 'exists:shop_products,id'],
            'items.*.quantity'        => ['required', 'integer', 'min:1'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'delivery_address_id'     => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'             => 'Имя покупателя обязательно.',
            'customer_phone.required'            => 'Телефон покупателя обязателен.',
            'customer_phone.regex'               => 'Телефон должен быть в корректном формате.',
            'customer_email.required'            => 'Email покупателя обязателен.',
            'customer_email.email'               => 'Email должен быть в корректном формате.',
            'personal_data_consent.accepted'     => 'Необходимо согласие на обработку персональных данных.',
            'payment_method.required'            => 'Способ оплаты обязателен.',
            'payment_method.enum'                => 'Выбран неподдерживаемый способ оплаты.',
            'payment_return_url.required'        => 'URL возврата после оплаты обязателен.',
            'payment_return_url.url'             => 'URL возврата после оплаты должен быть корректным.',
            'delivery_method.required'           => 'Способ доставки обязателен.',
            'delivery_price.required'            => 'Стоимость доставки обязательна.',
            'delivery_price.numeric'             => 'Стоимость доставки должна быть числом.',
            'delivery_price.min'                 => 'Стоимость доставки не может быть отрицательной.',
            'delivery_payload.required'          => 'Данные доставки обязательны.',
            'delivery_payload.array'             => 'Данные доставки должны быть объектом.',
            'recipient_name.required'            => 'ФИО получателя обязательно.',
            'recipient_phone.required'           => 'Телефон получателя обязателен.',
            'recipient_phone.regex'              => 'Телефон получателя должен быть в корректном формате.',
            'delivery_city.required'             => 'Город доставки обязателен.',
            'delivery_address.required_without'  => 'Укажите адрес доставки или пункт выдачи.',
            'delivery_pickup_point.required_without' => 'Укажите пункт выдачи или адрес доставки.',
            'items.required'                    => 'Список позиций обязателен.',
            'items.min'                         => 'Заказ должен содержать хотя бы одну позицию.',
            'items.*.shop_product_id.exists'    => 'Товар магазина не найден.',
            'items.*.shop_product_id.required'  => 'Товар магазина обязателен.',
            'items.*.quantity.min'              => 'Количество должно быть не менее 1.',
            'delivery_address_id.prohibited'    => 'Используйте новые поля доставки, delivery_address_id больше не поддерживается.',
        ];
    }
}
