<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сокращённое представление категории — используется внутри ProductShortResource
 * и ProductFullResource (поле category).
 *
 * @property int    $id
 * @property string $name
 * @property string $slug
 */
class ProductCategoryShortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
    }
}
