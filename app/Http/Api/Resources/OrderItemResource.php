<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int    $id
 * @property int    $product_id
 * @property int    $quantity
 * @property string $price
 * @property string $total
 * @property mixed  $product
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'product'    => $this->whenLoaded('product', fn () => new ProductShortResource($this->product)),
            'quantity'   => $this->quantity,
            'price'      => number_format((float) $this->price, 2, '.', ''),
            'total'      => number_format((float) $this->total, 2, '.', ''),
        ];
    }
}
