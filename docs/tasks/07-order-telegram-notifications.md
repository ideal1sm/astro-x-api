# Telegram-уведомления о новых заказах

**Приоритет:** P1

## Контекст

Блок ТЗ: менеджеры должны получать уведомление о новом заказе в Telegram.

## Что найдено в проекте

- Telegram-интеграций в проекте нет.
- В `config/services.php` нет telegram config.
- Данных заказа пока недостаточно для полного уведомления, пока не выполнены задачи по checkout/delivery/payment.

## Что нужно сделать

1. Выбрать формат интеграции: Telegram Bot API.
2. Добавить config/env для токена бота и chat id.
3. Спроектировать сервис отправки уведомления о новом заказе.
4. Определить формат сообщения и fallback на случай ошибок Telegram.
5. Решить, отправлять ли одно сообщение о заказе и отдельное сообщение об оплате.

## Backend

- Добавить сервис/клиент Telegram.
- Привязать отправку к событию создания заказа.
- Экранировать текст и URL под формат Telegram.
- Обработать сценарий отсутствия фото или слишком большого payload.

## Admin

- В Telegram-сообщении нужна ссылка на заказ в админке.

## Frontend

- Frontend вне репозитория.
- Дополнительный фронтовый код не требуется.

## Database

- Отдельные таблицы не обязательны.
- При необходимости можно заложить журнал отправок как future enhancement.

## API

- Новые публичные API endpoints не обязательны.

## Swagger/OpenAPI

- Изменения не обязательны, если endpoint не добавляется.

## Notifications

- Telegram-сообщение должно включать:
  - номер заказа;
  - клиента;
  - сумму;
  - оплату;
  - доставку;
  - ссылку на админку.
- Фото товара опционально, если это технически устойчиво.

## Tests

- Unit/feature-тесты отправки уведомления с mocked HTTP client.
- Корректная сборка текста сообщения.
- Корректное поведение при ошибке Telegram API.

## Acceptance criteria

- После создания заказа менеджер получает Telegram-уведомление.
- Сообщение содержит достаточный минимум для быстрой реакции.
- Ошибка Telegram не ломает создание заказа.

## Важные ограничения

- Не хранить bot token в коде.
- Не блокировать оформление заказа из-за ошибки внешнего Telegram API.

---

# Implementation status

## Status

Completed

## Implementation date

2026-06-01

## Changed files

- app/Listeners/SendShopOrderCreatedTelegramNotification.php
- app/Services/Notifications/TelegramBotClient.php
- app/Services/Notifications/ShopOrderTelegramNotificationService.php
- app/Providers/AppServiceProvider.php
- config/shop.php
- tests/Feature/ShopApiTest.php
- docs/tasks/07-order-telegram-notifications.md
- docs/tasks/README.md

## Database changes

- Нет изменений схемы БД

## API changes

- Внешние endpoints не добавлялись
- Telegram-уведомление привязано к существующему событию `ShopOrderCreated`, которое диспатчится после успешного создания заказа

## Admin changes

- В Telegram-сообщение добавлена абсолютная ссылка на заказ в Filament admin
- Изменения Filament resources не потребовались

## Swagger/OpenAPI

- Изменения не требовались, так как внешние API-контракты не менялись

## Tests

- Обновлён `tests/Feature/ShopApiTest.php`
- Добавлены сценарии:
  - Telegram-уведомление отправляется в настроенные chat ids
  - Telegram-уведомление не отправляется, если chat ids не настроены
  - ошибка Telegram API не ломает создание заказа

## Manual verification

- Настроить `SHOP_TELEGRAM_BOT_TOKEN` и `SHOP_TELEGRAM_CHAT_IDS`
- Оформить заказ через `POST /api/v1/shop/orders`
- Проверить, что менеджеры получают Telegram-сообщение с:
  - номером заказа
  - клиентом
  - суммой
  - оплатой
  - доставкой
  - составом заказа
  - ссылкой на админку
- Проверить, что при неверном token/chat id checkout остаётся успешным, а ошибка только логируется

## Notes

- Уведомление отправляется через Telegram Bot API методом `sendMessage`.
- Отдельное Telegram-сообщение на `payment.succeeded` в эту задачу не добавлялось; сообщение о новом заказе содержит текущий `payment_status`.
- Фото товара в Telegram не отправляется отдельным `sendPhoto`: в текущем scope оставлен текстовый формат, как более устойчивый к отсутствию изображения и ограничению payload.
- Telegram-конфигурация вынесена в env/config:
  - `SHOP_ORDER_TELEGRAM_NOTIFICATIONS_ENABLED`
  - `SHOP_TELEGRAM_BOT_TOKEN`
  - `SHOP_TELEGRAM_CHAT_IDS`
- Ошибка внешнего Telegram API логируется и не ломает checkout.
- Автотесты и `pint` в текущем окружении не запускаются из-за отсутствующего PHP extension `mbstring`.
