<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopProductShortResource extends JsonResource
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
                    ? new ShopCategoryShortResource($this->category)
                    : null,
            ),
            'images'            => ShopProductImageResource::collection(
                $this->whenLoaded('images'),
            ),
            'created_at'        => $this->created_at,
        ];
    }
}
