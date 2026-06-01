<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopOrderFullResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'status'                => $this->status->value,
            'payment_method'        => $this->payment_method?->value,
            'payment_status'        => $this->payment_status?->value,
            'payment_amount'        => $this->payment_amount !== null ? number_format((float) $this->payment_amount, 2, '.', '') : null,
            'payment_confirmation_url' => $this->payment_confirmation_url,
            'payment_id'            => $this->yookassa_payment_id,
            'payment_paid_at'       => $this->payment_paid_at,
            'items_total'           => number_format((float) $this->items_total, 2, '.', ''),
            'delivery_method'       => $this->delivery_method,
            'delivery_price'        => number_format((float) $this->delivery_price, 2, '.', ''),
            'delivery_payload'      => $this->delivery_payload ?? [],
            'recipient_name'        => $this->recipient_name,
            'recipient_phone'       => $this->recipient_phone,
            'delivery_city'         => $this->delivery_city,
            'delivery_address'      => $this->delivery_address,
            'delivery_pickup_point' => $this->delivery_pickup_point,
            'delivery_comment'      => $this->delivery_comment,
            'total'                 => number_format((float) $this->total, 2, '.', ''),
            'customer_name'         => $this->customer_name,
            'customer_phone'        => $this->customer_phone,
            'customer_email'        => $this->customer_email,
            'personal_data_consent' => $this->personal_data_consent_at !== null,
            'payment_failure_reason' => $this->payment_failure_reason,
            'items'                 => ShopOrderItemResource::collection($this->whenLoaded('items')),
            'notes'      => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
