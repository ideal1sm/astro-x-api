<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Полная карточка товара — для GET /catalog/products/{id}.
 * Включает все поля из таблицы products.
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $short_description
 * @property string      $price
 * @property string|null $color
 * @property string|null $composition
 * @property string|null $inlay
 * @property string|null $brand
 * @property string|null $description
 * @property string|null $lock_type
 * @property string|null $length
 * @property string|null $production
 * @property array|null  $zodiac_signs
 */
class ProductFullResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'short_description' => $this->short_description,
            'price'             => number_format((float) $this->price, 2, '.', ''),
            'color'             => $this->color,
            'composition'       => $this->composition,
            'inlay'             => $this->inlay,
            'brand'             => $this->brand,
            'description'       => $this->description,
            'lock_type'         => $this->lock_type,
            'length'            => $this->length,
            'production'        => $this->production,
            'zodiac_signs'      => $this->zodiac_signs ?? [],
            'category'          => $this->whenLoaded(
                'category',
                fn () => $this->category
                    ? new ProductCategoryShortResource($this->category)
                    : null,
            ),
            'images'            => ProductImageResource::collection(
                $this->whenLoaded('images'),
            ),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
