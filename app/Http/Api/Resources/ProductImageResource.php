<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int    $id
 * @property int    $product_id
 * @property string $url  // accessor из ProductImage::getUrlAttribute()
 */
class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'  => $this->id,
            'url' => $this->url,
        ];
    }
}
