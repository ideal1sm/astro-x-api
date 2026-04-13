<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'  => $this->id,
            'url' => $this->url,
        ];
    }
}
