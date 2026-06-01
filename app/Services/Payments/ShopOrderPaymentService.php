<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ShopOrder;
use App\Services\CreateShopOrderService;
use Illuminate\Support\Facades\DB;

class ShopOrderPaymentService
{
    public function __construct(
        private readonly YookassaClient $yookassaClient,
        private readonly CreateShopOrderService $createShopOrderService,
    ) {}

    public function initiatePayment(ShopOrder $order, array $validated): ShopOrder
    {
        $paymentMethod = PaymentMethod::from($validated['payment_method']);

        return match ($paymentMethod) {
            PaymentMethod::Yookassa => $this->initiateYookassaPayment($order, $validated['payment_return_url']),
        };
    }

    public function handleYookassaWebhook(array $validated): void
    {
        $paymentId = (string) data_get($validated, 'object.id');
        $payment = $this->yookassaClient->getPayment($paymentId);

        DB::transaction(function () use ($paymentId, $payment): void {
            $order = ShopOrder::query()
                ->where('yookassa_payment_id', $paymentId)
                ->with('items.product')
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                return;
            }

            $previousPaymentStatus = $order->payment_status;
            $paymentStatus = PaymentStatus::fromYookassaStatus((string) data_get($payment, 'status', 'pending'));

            $updates = [
                'payment_status' => $paymentStatus,
                'payment_amount' => $this->normalizeAmount(data_get($payment, 'amount.value', $order->total)),
                'payment_confirmation_url' => data_get($payment, 'confirmation.confirmation_url'),
                'payment_failure_reason' => data_get($payment, 'cancellation_details.reason'),
                'payment_payload' => $payment,
            ];

            if ($paymentStatus === PaymentStatus::Succeeded) {
                $updates['payment_paid_at'] = $this->resolvePaidAt($payment);

                if ($order->status === OrderStatus::Created) {
                    $updates['status'] = OrderStatus::InProgress;
                }
            }

            if ($paymentStatus === PaymentStatus::Canceled) {
                if ($previousPaymentStatus !== PaymentStatus::Canceled) {
                    $this->createShopOrderService->restoreReservedStock($order);
                }

                if ($order->status === OrderStatus::Created) {
                    $updates['status'] = OrderStatus::Canceled;
                }
            }

            $order->fill($updates)->save();
        });
    }

    public function rollbackUnpaidOrder(ShopOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $this->createShopOrderService->restoreReservedStock($order);
            $order->items()->delete();
            $order->delete();
        });
    }

    private function initiateYookassaPayment(ShopOrder $order, string $returnUrl): ShopOrder
    {
        $payment = $this->yookassaClient->createPayment([
            'amount' => [
                'value' => $this->normalizeAmount($order->total),
                'currency' => 'RUB',
            ],
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $returnUrl,
            ],
            'description' => "Оплата заказа Мёд #{$order->id}",
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $order->forceFill([
            'payment_method' => PaymentMethod::Yookassa,
            'payment_status' => PaymentStatus::fromYookassaStatus((string) data_get($payment, 'status', 'pending')),
            'yookassa_payment_id' => data_get($payment, 'id'),
            'payment_amount' => $this->normalizeAmount(data_get($payment, 'amount.value', $order->total)),
            'payment_confirmation_url' => data_get($payment, 'confirmation.confirmation_url'),
            'payment_payload' => $payment,
            'payment_initiated_at' => now(),
        ])->save();

        return $order->fresh(['items.product.images', 'items.product.category', 'user']);
    }
    private function normalizeAmount(string|float|int|null $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function resolvePaidAt(array $payment): string
    {
        $paidAt = data_get($payment, 'captured_at') ?? data_get($payment, 'paid_at');

        return (string) ($paidAt ?: now()->toDateTimeString());
    }
}
