<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'shop_product_id' => $this->shop_product_id,
            'product'         => $this->whenLoaded('product', fn () => new ShopProductShortResource($this->product)),
            'quantity'        => $this->quantity,
            'price'           => number_format((float) $this->price, 2, '.', ''),
            'total'           => number_format((float) $this->total, 2, '.', ''),
        ];
    }
}
