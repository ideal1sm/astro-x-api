<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOrderService
{
    /**
     * Создаёт заказ со всеми позициями в одной транзакции.
     *
     * @param  User   $user
     * @param  array  $validated  Validated payload из CreateOrderRequest
     * @return Order  Заказ с загруженными items.product и deliveryAddress
     */
    public function execute(User $user, array $validated): Order
    {
        // Загружаем товары одним запросом
        $productIds = collect($validated['items'])->pluck('product_id')->unique()->all();
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Считаем суммы позиций и общий total
        $itemsData  = [];
        $orderTotal = '0.00';

        foreach ($validated['items'] as $item) {
            $product  = $products->get($item['product_id']);
            $price    = (float) $product->price;
            $quantity = (int) $item['quantity'];
            $total    = round($price * $quantity, 2);

            $itemsData[] = [
                'product_id' => $product->id,
                'quantity'   => $quantity,
                'price'      => $price,
                'total'      => $total,
            ];

            $orderTotal = (string) round((float) $orderTotal + $total, 2);
        }

        return DB::transaction(function () use ($user, $validated, $itemsData, $orderTotal): Order {
            $order = Order::create([
                'user_id'             => $user->id,
                'status'              => OrderStatus::Created,
                'total'               => $orderTotal,
                'delivery_address_id' => $validated['delivery_address_id'] ?? null,
                'notes'               => $validated['notes'] ?? null,
            ]);

            $now   = now();
            $rows  = array_map(fn (array $item) => array_merge($item, [
                'order_id'   => $order->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]), $itemsData);

            $order->items()->insert($rows);

            return $order->load(['items.product', 'deliveryAddress']);
        });
    }
}
