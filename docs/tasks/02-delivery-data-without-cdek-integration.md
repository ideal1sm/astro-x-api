# Сохранение данных доставки, переданных с фронта

**Приоритет:** P1

## Статус реализации

Реализовано.

## Что фактически сделано

- Checkout магазина переведён на новую модель доставки, независимую от `user_addresses`.
- В `shop_orders` добавлены и сохраняются:
  - `items_total`;
  - `delivery_method`;
  - `delivery_price`;
  - `delivery_payload`;
  - `recipient_name`;
  - `recipient_phone`;
  - `delivery_city`;
  - `delivery_address`;
  - `delivery_pickup_point`;
  - `delivery_comment`.
- `total` заказа теперь считается как `items_total + delivery_price`.
- `POST /api/v1/shop/orders` больше не принимает `delivery_address_id` в актуальном сценарии checkout.
- Валидация обновлена:
  - обязательны `delivery_method`, `delivery_price`, `delivery_payload`;
  - обязательны данные получателя;
  - обязателен город;
  - требуется либо `delivery_address`, либо `delivery_pickup_point`.
- API ответа заказа расширен delivery-полями и `items_total`.
- В админке заказа добавлен отдельный блок доставки.
- `openapi.json` синхронизирован под новый delivery contract без СДЭК API.
- Добавлены feature-тесты на:
  - guest checkout с ПВЗ;
  - auth checkout с курьерской доставкой;
  - валидацию delivery-полей;
  - расчёт итоговой суммы;
  - сохранение `delivery_payload`.

## Какие файлы были изменены при реализации

- `app/Http/Api/Requests/CreateShopOrderRequest.php`
- `app/Http/Api/Controllers/ShopOrderController.php`
- `app/Services/CreateShopOrderService.php`
- `app/Models/ShopOrder.php`
- `app/Http/Api/Resources/ShopOrderFullResource.php`
- `app/Filament/Resources/ShopOrders/ShopOrderResource.php`
- `database/factories/ShopOrderFactory.php`
- `tests/Feature/ShopApiTest.php`
- `openapi.json`

## Какие миграции были добавлены

- `database/migrations/2026_06_01_000001_add_delivery_fields_to_shop_orders_table.php`

## Что осталось за пределами этой задачи

- Интеграция со СДЭК не реализуется и не должна реализовываться.
- Backend не рассчитывает доставку и не получает список ПВЗ.
- Email/Telegram уведомления не реализуются в рамках этой задачи, но все нужные данные теперь сохраняются в заказе.
- Frontend в репозитории отсутствует; backend только фиксирует API contract и persistence layer.

## Контекст

Блок ТЗ про СДЭК изменён бизнес-уточнением: интеграции со СДЭК не будет. Вместо этого backend должен сохранять способ доставки, delivery payload и стоимость доставки, которые пришли с фронта.

## Что найдено в проекте

- В заказе магазина сейчас есть только `delivery_address_id` на `user_addresses`: `app/Models/ShopOrder.php`, `database/migrations/2026_04_13_000003_create_shop_orders_table.php`.
- `CreateShopOrderRequest` и `CreateShopOrderService` умеют работать только с `delivery_address_id` и `notes`.
- В `openapi.json` для shop-orders пока заложен старый формат доставки через `delivery_address`.
- Интеграций СДЭК в коде нет.

## Что нужно сделать

1. Спроектировать новую модель хранения доставки в `shop_orders`, независимую от `user_addresses`.
2. Определить минимальный обязательный набор полей:
  - способ доставки;
  - стоимость доставки;
  - итоговый delivery payload от фронта;
  - ФИО и телефон получателя;
  - город / адрес / выбранный ПВЗ в зависимости от сценария.
3. Решить, хранить ли payload одной JSON-колонкой плюс денормализованные ключевые поля для админки и уведомлений.
4. Перевести создание заказа магазина на новую схему доставки.
5. Обновить отображение доставки в админке, email, Telegram и API.

## Backend

- Убрать зависимость checkout от `delivery_address_id` как основного сценария магазина.
- Добавить валидацию payload доставки из фронта.
- Сохранять стоимость доставки отдельно от стоимости товаров.
- Рассчитывать итог заказа как `items_total + delivery_price`, если это согласовано с фронтом и платёжным сценарием.
- Явно сериализовать/нормализовать payload для дальнейшего использования менеджерами.

## Admin

- Показать в карточке заказа:
  - способ доставки;
  - стоимость доставки;
  - данные получателя;
  - адрес или ПВЗ;
  - исходный payload доставки при необходимости.

## Frontend

- Frontend вне репозитория.
- Frontend должен сам передавать:
  - `delivery_method`;
  - `delivery_price`;
  - `delivery_payload`;
  - поля получателя;
  - адресные данные или данные ПВЗ.
- Backend не рассчитывает доставку и не получает список ПВЗ.

## Database

- Новая миграция для `shop_orders`:
  - поля `delivery_method`, `delivery_price`;
  - JSON-поле для сохранения delivery payload;
  - отдельные денормализованные поля адреса/получателя при необходимости;
  - возможно, поля `items_total` и `grand_total`, если нужно отделить товары от доставки.

## API

- Изменить `POST /api/v1/shop/orders`.
- Обновить `GET /api/v1/shop/orders/{id}` и список заказов, если в кратком ответе нужны данные доставки.
- Зафиксировать формат `delivery_payload`.

## Swagger/OpenAPI

- Удалить из контрактов ожидание backend-интеграции со СДЭК.
- Описать новый request payload доставки.
- Обновить response со стоимостью и данными доставки.

## Notifications

- Email и Telegram должны использовать сохранённые в заказе данные доставки, а не обращаться к внешнему сервису.

## Tests

- Создание заказа с разными способами доставки.
- Валидация `delivery_method`, `delivery_price`, `delivery_payload`.
- Проверка сохранения JSON payload и денормализованных полей.
- Проверка расчёта итоговой суммы заказа с доставкой.

## Acceptance criteria

- Backend сохраняет способ доставки, данные доставки и стоимость доставки, полученные с фронта.
- Менеджер видит всю нужную информацию в админке без интеграции со СДЭК.
- API не зависит от внешнего сервиса доставки.

## Важные ограничения

- Не подключать СДЭК API.
- Не рассчитывать доставку на backend.
- Не получать список ПВЗ через backend.
- Не создавать отправление в СДЭК.
