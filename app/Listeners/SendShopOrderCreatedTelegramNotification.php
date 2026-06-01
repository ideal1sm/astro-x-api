<?php

namespace App\Listeners;

use App\Events\ShopOrderCreated;
use App\Services\Notifications\ShopOrderTelegramNotificationService;

class SendShopOrderCreatedTelegramNotification
{
    public function __construct(private readonly ShopOrderTelegramNotificationService $notificationService) {}

    public function handle(ShopOrderCreated $event): void
    {
        $this->notificationService->sendCreatedOrderNotification($event->order);
    }
}
