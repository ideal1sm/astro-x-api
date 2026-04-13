<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopOrderShortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status->value,
            'total'       => number_format((float) $this->total, 2, '.', ''),
            'items_count' => $this->items_count ?? 0,
            'created_at'  => $this->created_at,
        ];
    }
}
