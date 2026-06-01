<?php

namespace App\Listeners;

use App\Events\ShopOrderCreated;
use App\Services\Notifications\ShopOrderEmailNotificationService;

class SendShopOrderCreatedEmailNotification
{
    public function __construct(private readonly ShopOrderEmailNotificationService $notificationService) {}

    public function handle(ShopOrderCreated $event): void
    {
        $this->notificationService->sendCreatedOrderEmail($event->order);
    }
}
