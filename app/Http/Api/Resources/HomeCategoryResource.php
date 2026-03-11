<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Блок категории на главной странице.
 * Содержит до 8 кратких карточек товаров (ограничение на уровне коллекции).
 *
 * Ожидает, что связь 'products.images' загружена через eager loading.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property mixed       $products
 */
class HomeCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'slug'     => $this->slug,
            // image у категорий в БД отсутствует — поле возвращаем как null
            'image'    => null,
            'products' => ProductShortResource::collection(
                $this->whenLoaded('products', fn () => $this->products->take(8)),
            ),
        ];
    }
}
