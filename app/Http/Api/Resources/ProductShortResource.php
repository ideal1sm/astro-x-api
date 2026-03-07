<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Товар в списке (каталог, главная страница, поиск).
 * Тяжёлые поля (description, lock_type, length, production, zodiac_signs)
 * отсутствуют — используй ProductFullResource для деталки.
 *
 * Ожидает, что связи 'images' и 'category' загружены (with/whenLoaded).
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $short_description
 * @property string      $price
 * @property string|null $color
 * @property string|null $composition
 * @property string|null $inlay
 * @property string|null $brand
 */
class ProductShortResource extends JsonResource
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
        ];
    }
}
