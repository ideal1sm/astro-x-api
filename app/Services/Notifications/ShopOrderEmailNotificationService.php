<?php

namespace App\Services\Notifications;

use App\Mail\ShopOrderCreatedManagerMail;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ShopOrderEmailNotificationService
{
    public function sendCreatedOrderEmail(ShopOrder $order): void
    {
        if (! config('shop.notifications.email.enabled', true)) {
            return;
        }

        $recipients = config('shop.notifications.email.recipients', []);

        if (! is_array($recipients) || $recipients === []) {
            return;
        }

        $order->loadMissing(['items.product.images', 'items.product.category', 'user']);

        try {
            Mail::to($recipients)->send(new ShopOrderCreatedManagerMail($order));
        } catch (Throwable $exception) {
            Log::warning('Failed to send shop order email notification.', [
                'shop_order_id' => $order->id,
                'recipients' => $recipients,
                'exception' => $exception,
            ]);
        }
    }
}
