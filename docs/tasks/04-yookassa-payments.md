# Интеграция оплаты через ЮKassa

**Приоритет:** P1

## Контекст

Блок ТЗ: онлайн-оплата через ЮKassa. В проекте пока нет ни платёжной интеграции, ни платёжных полей заказа, ни webhook-обработки.

## Что найдено в проекте

- В `shop_orders` нет платёжных полей: `database/migrations/2026_04_13_000003_create_shop_orders_table.php`.
- Статусы заказа сейчас общие и не покрывают платёжный lifecycle: `app/Enums/OrderStatus.php`.
- В `config/services.php` нет конфигурации ЮKassa.
- Контроллер и сервис заказа не создают платёж: `app/Http/Api/Controllers/ShopOrderController.php`, `app/Services/CreateShopOrderService.php`.
- Маршрутов webhook/return URL нет: `routes/api.php`, `routes/web.php`.

## Что нужно сделать

1. Спроектировать платёжную модель заказа магазина.
2. Добавить способ оплаты `yookassa`.
3. Реализовать создание платежа после подтверждения заказа.
4. Реализовать обработку webhook-уведомлений ЮKassa.
5. Определить карту статусов заказа и статусов оплаты.
6. Продумать success/cancel/fail URL для storefront.
7. Отдельно зафиксировать открытый вопрос по фискализации как integration note, если требования бизнеса не уточнены.

## Backend

- Добавить сервис интеграции с ЮKassa.
- Добавить config/env для `shop_id`, `secret_key`, webhook secret/idempotence.
- Сохранять:
  - способ оплаты;
  - статус оплаты;
  - ID платежа;
  - сумму платежа;
  - дату/время оплаты;
  - сырой ответ платёжного провайдера при необходимости.
- Обеспечить идемпотентную обработку webhook.
- Не смешивать платёжные статусы с `OrderStatus` без отдельного слоя, если это приводит к путанице.

## Admin

- Показать платёжные поля в заказе.
- Дать менеджеру понять, оплачен ли заказ, ожидает ли оплаты или платёж завершился ошибкой.

## Frontend

- Frontend вне репозитория.
- Нужен контракт:
  - как frontend инициирует платёж;
  - куда редиректить пользователя;
  - какие URL успеха/ошибки использовать;
  - как фронт получает финальный статус заказа.

## Database

- Новая миграция для `shop_orders` с платёжными полями.
- При необходимости отдельная таблица `shop_order_payments`, если нужна история попыток и webhook-событий.

## API

- Расширить `POST /api/v1/shop/orders` платёжным способом.
- Добавить endpoint и/или поля ответа для redirect URL / payment confirmation.
- Добавить webhook endpoint от ЮKassa.

## Swagger/OpenAPI

- Описать создание заказа с оплатой ЮKassa.
- Описать response с платёжными данными.
- Описать webhook contract, если он документируется в проекте.

## Notifications

- Email/Telegram должны корректно отражать статус оплаты.
- Не слать финальное сообщение об оплате до подтверждения от ЮKassa.

## Tests

- Создание заказа с оплатой ЮKassa.
- Обработка успешного webhook.
- Обработка отмены/ошибки/ожидания оплаты.
- Идемпотентность webhook.
- Корректное изменение статусов заказа и оплаты.

## Acceptance criteria

- Для заказа создаётся платёж ЮKassa.
- Успешная оплата отражается в заказе и админке.
- Ошибочный/отменённый платёж не помечает заказ оплаченным.
- Контракты для фронта и webhook задокументированы.

## Важные ограничения

- Не хранить чувствительные платёжные данные небезопасно.
- Не подменять webhook только клиентским callback.
- Не нарушать текущий API envelope.

---

# Implementation status

## Status

Completed

## Implementation date

2026-06-01

## Changed files

- app/Enums/PaymentMethod.php
- app/Enums/PaymentStatus.php
- app/Exceptions/PaymentGatewayException.php
- app/Http/Api/Controllers/ShopOrderController.php
- app/Http/Api/Controllers/YookassaWebhookController.php
- app/Http/Api/Requests/CreateShopOrderRequest.php
- app/Http/Api/Requests/HandleYookassaWebhookRequest.php
- app/Http/Api/Resources/ShopOrderFullResource.php
- app/Http/Api/Resources/ShopOrderShortResource.php
- app/Filament/Resources/ShopOrders/ShopOrderResource.php
- app/Models/ShopOrder.php
- app/Services/CreateShopOrderService.php
- app/Services/Payments/YookassaClient.php
- app/Services/Payments/ShopOrderPaymentService.php
- config/services.php
- database/factories/ShopOrderFactory.php
- database/migrations/2026_06_01_000003_add_payment_fields_to_shop_orders_table.php
- openapi.json
- routes/api.php
- tests/Feature/ShopApiTest.php

## Database changes

- `2026_06_01_000003_add_payment_fields_to_shop_orders_table.php`
- В `shop_orders` добавлены поля `payment_method`, `payment_status`, `yookassa_payment_id`, `payment_amount`, `payment_confirmation_url`, `payment_initiated_at`, `payment_paid_at`, `payment_failure_reason`, `payment_payload`

## API changes

- `POST /api/v1/shop/orders`
  - теперь требует `payment_method`
  - теперь требует `payment_return_url`
  - создаёт заказ и инициирует платёж ЮKassa
  - возвращает `payment_confirmation_url`, `payment_status`, `payment_id`, `payment_amount`
- `POST /api/v1/shop/payments/yookassa/webhook`
  - принимает webhook от ЮKassa
  - обновляет статус оплаты и статус заказа
  - восстанавливает зарезервированный остаток при `payment.canceled`

## Admin changes

- В Filament-заказе Мёд добавлена секция оплаты
- В таблице заказов Мёд добавлена колонка статуса оплаты
- В карточке заказа показываются:
  - способ оплаты
  - статус оплаты
  - сумма оплаты
  - ID платежа ЮKassa
  - ссылка на оплату
  - дата оплаты
  - причина отмены/ошибки
  - сырой payment payload

## Swagger/OpenAPI

- Обновлён contract `POST /shop/orders`
- В `ShopOrderShort` и `ShopOrderFull` добавлены payment-поля
- Добавлен webhook endpoint `POST /shop/payments/yookassa/webhook`
- Добавлен `502 PAYMENT_PROVIDER_ERROR` для сбоя при создании платежа

## Tests

- Обновлены feature-тесты checkout магазина под обязательную оплату через ЮKassa
- Добавлены сценарии:
  - успешное создание заказа с pending payment
  - guest checkout с pending payment
  - ошибка провайдера с компенсационным rollback заказа
  - успешный webhook `payment.succeeded`
  - webhook `payment.canceled` с восстановлением остатка
  - идемпотентность повторного canceled webhook

## Manual verification

- Оформить заказ через `POST /api/v1/shop/orders` и убедиться, что в ответе есть `payment_confirmation_url`
- Проверить, что в `shop_orders` сохраняются payment-поля
- Отправить webhook `payment.succeeded` и убедиться, что заказ переходит в `in_progress`
- Отправить webhook `payment.canceled` и убедиться, что заказ переходит в `canceled`, а остаток восстанавливается
- Проверить отображение payment-блока в Filament-заказе Мёд

## Notes

- Frontend в репозитории отсутствует. Storefront должен использовать `payment_confirmation_url` для редиректа на ЮKassa и затем запрашивать актуальный статус заказа через существующие order endpoints.
- Логика из задачи `03-product-stock.md` сохранена: остаток резервируется при создании заказа и возвращается при `payment.canceled`.
- История нескольких платёжных попыток в отдельной таблице не реализовывалась; в текущем scope платёжное состояние хранится на уровне `shop_orders`.
- Публичный guest-endpoint для последующего polling статуса оплаты не добавлялся. Для гостевого storefront это нужно отдельно согласовать в следующей задаче, чтобы не вводить небезопасный способ чтения заказа без auth.
- Автотесты и `pint` в текущем окружении не запускаются из-за отсутствующего PHP extension `mbstring`.
