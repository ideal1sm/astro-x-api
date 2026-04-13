<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopOrderFullResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status->value,
            'total'            => number_format((float) $this->total, 2, '.', ''),
            'items'            => ShopOrderItemResource::collection($this->whenLoaded('items')),
            'delivery_address' => $this->whenLoaded(
                'deliveryAddress',
                fn () => $this->deliveryAddress
                    ? new UserAddressResource($this->deliveryAddress)
                    : null,
            ),
            'notes'      => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
