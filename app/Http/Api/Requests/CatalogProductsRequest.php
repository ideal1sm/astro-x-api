<?php

namespace App\Http\Api\Requests;

class CatalogProductsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Нормализуем has_images до PHP bool до того, как запустятся правила валидации.
     * Query-параметр приходит строкой ("true"/"false"), а Laravel's 'boolean' rule
     * принимает только 1/0/"1"/"0"/true/false.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('has_images')) {
            $this->merge([
                'has_images' => filter_var(
                    $this->input('has_images'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ),
            ]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'page'           => ['integer', 'min:1'],
            'limit'          => ['integer', 'min:1', 'max:100'],
            'sort'           => ['string', 'in:price,-price,name,-name,created_at,-created_at'],
            // category_id takes precedence when both category_id and category_slug are supplied;
            // category_slug is silently ignored in that case (handled in controller).
            'category_id'    => ['integer', 'exists:product_categories,id'],
            'category_slug'  => ['string', 'max:255'],
            'brand'          => ['string', 'max:255'],
            'color'          => ['string', 'max:255'],
            'composition'    => ['string', 'max:255'],
            'inlay'          => ['string', 'max:255'],
            'lock_type'      => ['string', 'max:255'],
            'production'     => ['string', 'max:255'],
            'price_min'      => ['numeric', 'min:0'],
            'price_max'      => ['numeric', 'min:0'],
            'zodiac_signs'   => ['array'],
            'zodiac_signs.*' => [
                'string',
                'in:aries,taurus,gemini,cancer,leo,virgo,libra,scorpio,sagittarius,capricorn,aquarius,pisces',
            ],
            'has_images'     => ['boolean'],
        ];

        // Добавляем gte только когда price_min реально передан, чтобы не требовать
        // price_min при использовании одного price_max.
        if ($this->filled('price_min')) {
            $rules['price_max'][] = 'gte:price_min';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sort.in'            => 'Допустимые значения сортировки: price, -price, name, -name, created_at, -created_at.',
            'zodiac_signs.*.in'  => 'Недопустимый знак зодиака.',
            'category_id.exists' => 'Категория не найдена.',
            'price_max.gte'      => 'price_max должен быть не меньше price_min.',
        ];
    }
}
