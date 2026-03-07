<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Полный объект категории — для GET /catalog/categories и GET /home.
 *
 * Поля show_on_home и show_in_catalog возвращаются через whenNotNull:
 * до добавления миграции они отсутствуют в модели и не попадут в ответ.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string|null $description
 * @property bool|null   $show_on_home
 * @property bool|null   $show_in_catalog
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'show_on_home'    => $this->whenNotNull($this->show_on_home),
            'show_in_catalog' => $this->whenNotNull($this->show_in_catalog),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
