<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateShopOrderService
{
    public function execute(User $user, array $validated): ShopOrder
    {
        $productIds = collect($validated['items'])->pluck('shop_product_id')->unique()->all();
        $products = ShopProduct::whereIn('id', $productIds)->get()->keyBy('id');

        $itemsData = [];
        $orderTotal = '0.00';

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['shop_product_id']);
            $price = (float) $product->price;
            $quantity = (int) $item['quantity'];
            $total = round($price * $quantity, 2);

            $itemsData[] = [
                'shop_product_id' => $product->id,
                'quantity'        => $quantity,
                'price'           => $price,
                'total'           => $total,
            ];

            $orderTotal = (string) round((float) $orderTotal + $total, 2);
        }

        return DB::transaction(function () use ($user, $validated, $itemsData, $orderTotal): ShopOrder {
            $order = ShopOrder::create([
                'user_id'             => $user->id,
                'status'              => OrderStatus::Created,
                'total'               => $orderTotal,
                'delivery_address_id' => $validated['delivery_address_id'] ?? null,
                'notes'               => $validated['notes'] ?? null,
            ]);

            $now = now();
            $rows = array_map(fn (array $item) => array_merge($item, [
                'shop_order_id' => $order->id,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]), $itemsData);

            $order->items()->insert($rows);

            return $order->load(['items.product.images', 'items.product.category', 'deliveryAddress']);
        });
    }
}
