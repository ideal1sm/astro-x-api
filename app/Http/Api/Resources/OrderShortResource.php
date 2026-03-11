<?php

namespace App\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Заказ в списке (без позиций).
 *
 * Ожидает withCount('items') для поля items_count.
 *
 * @property int    $id
 * @property string $status  (OrderStatus enum value)
 * @property string $total
 * @property int    $items_count
 * @property mixed  $created_at
 */
class OrderShortResource extends JsonResource
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
