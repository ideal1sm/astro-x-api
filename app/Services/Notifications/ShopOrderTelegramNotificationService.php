<?php

namespace App\Services\Notifications;

use App\Filament\Resources\ShopOrders\ShopOrderResource;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopOrderTelegramNotificationService
{
    public function __construct(private readonly TelegramBotClient $telegramBotClient) {}

    public function sendCreatedOrderNotification(ShopOrder $order): void
    {
        if (! config('shop.notifications.telegram.enabled', true)) {
            return;
        }

        $botToken = (string) config('shop.notifications.telegram.bot_token');
        $chatIds = config('shop.notifications.telegram.chat_ids', []);

        if ($botToken === '' || ! is_array($chatIds) || $chatIds === []) {
            return;
        }

        $order->loadMissing(['items.product.category', 'user']);

        $text = $this->buildMessage($order);

        foreach ($chatIds as $chatId) {
            try {
                $this->telegramBotClient->sendMessage($botToken, (string) $chatId, [
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
            } catch (Throwable $exception) {
                Log::warning('Failed to send shop order Telegram notification.', [
                    'shop_order_id' => $order->id,
                    'chat_id' => $chatId,
                    'exception' => $exception,
                ]);
            }
        }
    }

    private function buildMessage(ShopOrder $order): string
    {
        $items = $order->items
            ->map(fn ($item) => '• ' . $this->escape($item->product?->name ?? 'Товар удалён') . ' × ' . (int) $item->quantity)
            ->implode("\n");

        $deliveryDestination = $order->delivery_address
            ?: $order->delivery_pickup_point
            ?: '—';

        $adminOrderUrl = ShopOrderResource::getUrl('edit', ['record' => $order], true, 'admin');

        return implode("\n", array_filter([
            '<b>Новый заказ Мёд #' . $order->id . '</b>',
            'Дата: ' . $this->escape($order->created_at?->format('d.m.Y H:i') ?? '—'),
            'Клиент: ' . $this->escape($order->customer_name),
            'Телефон: ' . $this->escape($order->customer_phone),
            'Сумма: ' . number_format((float) $order->total, 2, '.', ' ') . ' ₽',
            'Оплата: ' . $this->escape($order->payment_status?->label() ?? '—'),
            'Доставка: ' . $this->escape($order->delivery_method . ', ' . $order->delivery_city),
            'Адрес / ПВЗ: ' . $this->escape($deliveryDestination),
            $items !== '' ? "Состав:\n{$items}" : null,
            $order->notes ? 'Комментарий: ' . $this->escape($order->notes) : null,
            '<a href="' . $this->escape($adminOrderUrl) . '">Открыть заказ в админке</a>',
        ]));
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
