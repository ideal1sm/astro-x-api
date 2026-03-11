<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'title'       => $this->title,
            'country'     => $this->country,
            'city'        => $this->city,
            'street'      => $this->street,
            'apartment'   => $this->apartment,
            'postal_code' => $this->postal_code,
            'is_default'  => $this->is_default,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
