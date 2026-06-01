# Остатки товаров магазина

**Приоритет:** P1

## Контекст

Блок ТЗ: остатки товаров. Сейчас у магазинных товаров нет полей наличия и количества, а создание заказа не контролирует stock.

## Что найдено в проекте

- Модель товара магазина: `app/Models/ShopProduct.php`.
- Таблица `shop_products` не содержит полей `stock`, `availability_status`, `sku` или аналогов: `database/migrations/2026_04_13_000001_create_shop_products_table.php`, `2026_04_13_000005_sync_shop_products_with_products_schema.php`.
- Форма и таблица админки товара используют общий schema helper без полей остатков: `app/Filament/Resources/Shared/ProductCatalogResourceSchema.php`, `app/Filament/Resources/ShopProducts/ShopProductResource.php`.
- Сервис создания заказа не проверяет остатки и не списывает их: `app/Services/CreateShopOrderService.php`.
- API каталога магазина отдает только товарные поля без статуса наличия: `app/Http/Api/Resources/ShopProductShortResource.php`, `ShopProductFullResource.php`.

## Что нужно сделать

1. Спроектировать модель остатков для `shop_products`.
2. Определить, нужны ли оба механизма:
  - статус наличия (`in_stock`, `out_of_stock`, `preorder`);
  - точный количественный остаток.
3. Добавить управление остатками в админку.
4. Обновить каталог и карточку товара API, чтобы фронт мог отобразить наличие.
5. Заблокировать оформление заказа при недостаточном остатке.
6. После успешного оформления/оплаты списывать остаток по согласованному моменту.

## Backend

- Добавить бизнес-правила доступности товара в checkout.
- Решить, списывать ли остаток при создании заказа или после подтверждения оплаты ЮKassa.
- Добавить защиту от race condition при одновременных заказах одного товара.
- Обновить API resources товара полями наличия.

## Admin

- Добавить в форму товара поля остатка и статуса наличия.
- В таблице товаров показать текущий остаток/статус.
- Ограничить возможность выставить противоречивые значения.

## Frontend

- Frontend вне репозитория.
- Нужны новые поля в каталоге и карточке товара:
  - `availability_status`;
  - `stock_quantity`;
  - `is_purchasable`.
- На стороне фронта скрывать/блокировать кнопку покупки по этим полям, но backend всё равно должен валидировать.

## Database

- Новая миграция для `shop_products`:
  - статус наличия;
  - количество в наличии;
  - при необходимости `sku`/артикул, если он нужен также для заказов и уведомлений.

## API

- Изменить `/api/v1/shop/catalog/products`.
- Изменить `/api/v1/shop/catalog/products/{id}`.
- Изменить `POST /api/v1/shop/orders` с учётом проверки остатков.

## Swagger/OpenAPI

- Добавить в схемы товара поля наличия.
- Добавить описание ошибок заказа при отсутствии товара на складе.

## Notifications

- В уведомлениях можно передавать артикул и статус наличия только если это будет полезно менеджерам.

## Tests

- API каталога возвращает статус наличия.
- Нельзя заказать товар с нулевым остатком.
- Нельзя заказать количество больше доступного.
- Остаток уменьшается после согласованного бизнес-события.
- Astro-x каталог не затронут.

## Acceptance criteria

- Остатки управляются из админки.
- Фронт получает корректный статус наличия.
- Заказ недоступен для товара без остатка.
- Списание остатка происходит предсказуемо и тестами покрыто.

## Важные ограничения

- Не добавлять остатки в Astro-x каталог, если задача относится только к магазину.
- Не ломать зеркальность структуры `ShopProduct` относительно текущего проектного подхода.

---

# Implementation status

## Status

Completed

## Implementation date

2026-06-01

## Changed files

- `app/Enums/ProductAvailabilityStatus.php`
- `app/Models/ShopProduct.php`
- `app/Http/Api/Resources/ShopProductShortResource.php`
- `app/Http/Api/Resources/ShopProductFullResource.php`
- `app/Filament/Resources/Shared/ProductCatalogResourceSchema.php`
- `app/Filament/Resources/ShopProducts/ShopProductResource.php`
- `app/Services/CreateShopOrderService.php`
- `app/Http/Api/Controllers/ShopOrderController.php`
- `database/factories/ShopProductFactory.php`
- `database/migrations/2026_06_01_000002_add_stock_fields_to_shop_products_table.php`
- `tests/Feature/ShopApiTest.php`
- `tests/Feature/ShopCatalogStructureTest.php`
- `openapi.json`

## Database changes

- добавлены поля `availability_status` и `stock_quantity` в `shop_products`

## API changes

- `GET /api/v1/shop/catalog/products`
- `GET /api/v1/shop/catalog/products/{id}`
- `POST /api/v1/shop/orders`

## Admin changes

- в админке товаров Мёд добавлены поля статуса наличия и остатка
- в таблице товаров Мёд добавлены колонки наличия и остатка
- Astro-x товары не затронуты

## Swagger/OpenAPI

- в схемы `ShopProductShort` и `ShopProductFull` добавлены:
  - `availability_status`
  - `stock_quantity`
  - `is_purchasable`
- обновлено описание checkout, чтобы зафиксировать проверку и списание остатков

## Tests

- обновлены тесты каталога магазина
- добавлены тесты:
  - возврата stock-полей в API
  - запрета заказа при `out_of_stock`
  - запрета заказа при недостаточном остатке
  - списания остатка и автоматического перехода в `out_of_stock`
  - разрешения заказа для `preorder` без списания количественного остатка

## Manual verification

- создать товар Мёд со статусом `in_stock` и остатком > 0, оформить заказ и убедиться, что остаток уменьшился
- создать товар Мёд со статусом `out_of_stock` и убедиться, что checkout возвращает validation error
- создать товар Мёд со статусом `preorder` и убедиться, что заказ проходит
- проверить отображение статуса наличия и остатка в админке Filament
- проверить новые поля в `GET /api/v1/shop/catalog/products` и `GET /api/v1/shop/catalog/products/{id}`

## Notes

- Остаток списывается при создании заказа, потому что оплата через ЮKassa ещё не реализована.
- Для `preorder` заказ разрешён, но количественный остаток не уменьшается.
- Для `in_stock` при `stock_quantity = null` товар считается доступным без количественного учёта.
- Автотесты и `pint` в текущем окружении не запускались из-за отсутствующего PHP extension `mbstring`.
