<?php

namespace App\Services;

use App\Enums\ProductAvailabilityStatus;
use App\Enums\OrderStatus;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateShopOrderService
{
    public function execute(?User $user, array $validated): ShopOrder
    {
        return DB::transaction(function () use ($user, $validated): ShopOrder {
            $productIds = collect($validated['items'])->pluck('shop_product_id')->unique()->all();
            $products = ShopProduct::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $requestedQuantities = collect($validated['items'])
                ->groupBy('shop_product_id')
                ->map(fn ($group) => (int) $group->sum('quantity'));

            $itemsData = [];
            $itemsTotal = '0.00';
            $errors = [];

            foreach ($validated['items'] as $index => $item) {
                $product = $products->get($item['shop_product_id']);

                if ($product === null) {
                    $errors["items.$index.shop_product_id"] = ['Товар магазина не найден.'];
                    continue;
                }

                $requestedQuantity = (int) $requestedQuantities->get($product->id, 0);

                if (! $product->isPurchasable($requestedQuantity)) {
                    $errors["items.$index.quantity"] = [$this->stockErrorMessage($product, $requestedQuantity)];
                    continue;
                }

                $price = (float) $product->price;
                $quantity = (int) $item['quantity'];
                $total = round($price * $quantity, 2);

                $itemsData[] = [
                    'shop_product_id' => $product->id,
                    'quantity'        => $quantity,
                    'price'           => $price,
                    'total'           => $total,
                ];

                $itemsTotal = (string) round((float) $itemsTotal + $total, 2);
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $deliveryPrice = (string) round((float) $validated['delivery_price'], 2);
            $orderTotal = (string) round((float) $itemsTotal + (float) $deliveryPrice, 2);

            $order = ShopOrder::create([
                'user_id'                   => $user?->id,
                'status'                    => OrderStatus::Created,
                'items_total'               => $itemsTotal,
                'delivery_method'           => $validated['delivery_method'],
                'delivery_price'            => $deliveryPrice,
                'delivery_payload'          => $validated['delivery_payload'],
                'recipient_name'            => $validated['recipient_name'],
                'recipient_phone'           => $validated['recipient_phone'],
                'delivery_city'             => $validated['delivery_city'],
                'delivery_address'          => $validated['delivery_address'] ?? null,
                'delivery_pickup_point'     => $validated['delivery_pickup_point'] ?? null,
                'delivery_comment'          => $validated['delivery_comment'] ?? null,
                'total'                     => $orderTotal,
                'customer_name'             => $validated['customer_name'],
                'customer_phone'            => $validated['customer_phone'],
                'customer_email'            => $validated['customer_email'],
                'personal_data_consent_at'  => now(),
                'notes'                     => $validated['notes'] ?? null,
            ]);

            $now = now();
            $rows = array_map(fn (array $item) => array_merge($item, [
                'shop_order_id' => $order->id,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]), $itemsData);

            $order->items()->insert($rows);

            foreach ($requestedQuantities as $productId => $requestedQuantity) {
                $product = $products->get((int) $productId);

                if (! $product || ! $product->shouldDecrementStock()) {
                    continue;
                }

                $newQuantity = max(0, (int) $product->stock_quantity - (int) $requestedQuantity);
                $product->stock_quantity = $newQuantity;

                if ($newQuantity === 0) {
                    $product->availability_status = ProductAvailabilityStatus::OutOfStock;
                }

                $product->save();
            }

            return $order->load(['items.product.images', 'items.product.category', 'user']);
        });
    }

    public function restoreReservedStock(ShopOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $groupedItems = ShopOrderItem::query()
                ->where('shop_order_id', $order->id)
                ->selectRaw('shop_product_id, SUM(quantity) as quantity')
                ->groupBy('shop_product_id')
                ->get();

            if ($groupedItems->isEmpty()) {
                return;
            }

            $products = ShopProduct::query()
                ->whereIn('id', $groupedItems->pluck('shop_product_id')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($groupedItems as $item) {
                $product = $products->get((int) $item->shop_product_id);

                if (! $product || ! $product->shouldDecrementStock()) {
                    continue;
                }

                $product->stock_quantity = (int) $product->stock_quantity + (int) $item->quantity;

                if ($product->stock_quantity > 0 && $product->availability_status === ProductAvailabilityStatus::OutOfStock) {
                    $product->availability_status = ProductAvailabilityStatus::InStock;
                }

                $product->save();
            }
        });
    }

    private function stockErrorMessage(ShopProduct $product, int $requestedQuantity): string
    {
        return match ($product->availability_status) {
            ProductAvailabilityStatus::OutOfStock => 'Товар отсутствует в наличии.',
            ProductAvailabilityStatus::Preorder => 'Товар недоступен для заказа.',
            ProductAvailabilityStatus::InStock => $product->stock_quantity !== null
                ? "Недостаточно остатка для заказа: доступно {$product->stock_quantity}, запрошено {$requestedQuantity}."
                : 'Товар недоступен для заказа.',
        };
    }
}
