<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopProductFullResource extends JsonResource
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
                    ? new ShopCategoryShortResource($this->category)
                    : null,
            ),
            'images'            => ShopProductImageResource::collection(
                $this->whenLoaded('images'),
            ),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
