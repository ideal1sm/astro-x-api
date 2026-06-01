# Контекст проекта и архитектуры

**Приоритет:** P1

## Контекст

Файл фиксирует текущее состояние проекта до начала доработок по ТЗ jewelry-med.ru. Нужен как опорный документ для всех последующих задач, чтобы не смешать каталог Astro-x и каталог магазина Мёд.

## Что найдено в проекте

- Backend: Laravel 12 / PHP 8.2 (`composer.json`).
- Auth: Laravel Sanctum (`composer.json`, `routes/api.php`).
- Admin: Filament v4 (`app/Providers/Filament/AdminPanelProvider.php`).
- Frontend в этом репозитории как storefront не найден. Есть только служебный Vite/Tailwind-слой Laravel и дефолтный `welcome` (`package.json`, `resources/js`, `resources/css`, `routes/web.php`, `resources/views/welcome.blade.php`).
- OpenAPI хранится в `openapi.json`.
- API routes описаны в `routes/api.php`.
- Модели лежат в `app/Models`.
- Миграции лежат в `database/migrations`.
- Тесты лежат в `tests/Feature` и `tests/Unit`.
- Для магазина Мёд уже выделены отдельные сущности:
  - категории: `app/Models/ShopCategory.php`, таблица `shop_categories`;
  - товары: `app/Models/ShopProduct.php`, таблица `shop_products`;
  - изображения товаров: `app/Models/ShopProductImage.php`, таблица `shop_product_images`;
  - заказы: `app/Models/ShopOrder.php`, таблица `shop_orders`;
  - позиции заказа: `app/Models/ShopOrderItem.php`, таблица `shop_order_items`.
- Для Astro-x остаются отдельные сущности `Product`, `ProductCategory`, `Order`.
- Общая таблица пользователей: `users` (`app/Models/User.php`, `database/migrations/0001_01_01_000000_create_users_table.php`).
- Разделение каталогов уже закреплено тестами (`tests/Feature/ShopCatalogStructureTest.php`, `tests/Feature/ShopApiTest.php`).
- Текущий storefront API магазина:
  - каталог: `/api/v1/shop/catalog/*`;
  - заказы: `/api/v1/shop/orders`.
- Текущие заказы магазина доступны только авторизованному пользователю (`routes/api.php`, `app/Http/Api/Controllers/ShopOrderController.php`).
- Текущая админка магазина:
  - категории: `app/Filament/Resources/ShopCategories`;
  - товары: `app/Filament/Resources/ShopProducts`;
  - заказы: `app/Filament/Resources/ShopOrders`;
  - пользователи: `app/Filament/Resources/Users`.
- Текущая схема заказа магазина минимальная: `user_id`, `status`, `total`, `delivery_address_id`, `notes` (`database/migrations/2026_04_13_000003_create_shop_orders_table.php`).
- Интеграции ЮKassa / Telegram / СДЭК / Wildberries в коде не найдены.
- Mail-конфиг стандартный Laravel (`config/mail.php`), сторонние сервисы пока без нужных интеграций (`config/services.php`).

## Что нужно сделать

1. Использовать этот документ как базу для всех задач из `docs/tasks`.
2. Во всех доработках магазина работать только с сущностями `Shop*`, если не затрагивается общая пользовательская часть.
3. Не смешивать таблицы `products` / `product_categories` с `shop_products` / `shop_categories`.
4. При проектировании checkout оставить `users` общими, но убрать обязательную авторизацию для создания магазинного заказа.
5. Для фронтенда, которого нет в репозитории, фиксировать API contract и payload отдельно в задачах.

## Backend

- Основная зона изменений: `app/Http/Api`, `app/Services`, `app/Models`, `routes/api.php`, `config/*`.
- Для магазины Мёд базовая точка входа в создание заказа: `app/Http/Api/Controllers/ShopOrderController.php` + `app/Services/CreateShopOrderService.php`.

## Admin

- Основные зоны изменений в админке: `app/Filament/Resources/ShopOrders`, `ShopProducts`, `Users`.

## Frontend

- Пользовательский storefront в репозитории не найден.
- Все frontend-задачи формулировать как внешний контракт для команды фронта: какие поля отправлять, какие страницы реализовать, какие данные ожидать от API.

## Database

- Все новые поля магазина добавлять в `shop_orders`, `shop_order_items`, `shop_products` и при необходимости связанные таблицы.
- Общую таблицу `users` не дублировать.

## API

- Все ответы должны сохранять текущий envelope `{code, data, message, errors}` (`app/Http/Api/Concerns/ApiResponse.php`).
- Изменения магазина должны идти через `/api/v1/shop/*`, если нет объективной причины расширять общие endpoints.

## Swagger/OpenAPI

- После изменения контрактов обновлять `openapi.json`.
- Не оставлять расхождение между реальной валидацией и описанием request/response.

## Notifications

- Email и Telegram сейчас отсутствуют как реализованные интеграции; их нужно закладывать отдельными задачами.

## Tests

- Обновлять feature-тесты API магазина.
- Добавить тесты на разделение Astro-x и shop-каталога при каждом изменении модели данных магазина.

## Acceptance criteria

- Архитектурные границы Astro-x и Мёд зафиксированы.
- Для каждой следующей задачи понятны точки изменения в коде.
- Все последующие задачи ссылаются на реальные файлы и таблицы текущего проекта.

## Важные ограничения

- Не ломать текущий каталог Astro-x.
- `users` остаются общими для Astro-x и магазина.
- Каталог магазина должен оставаться отдельным от Astro-x.
- Структуру магазинного каталога держать зеркальной текущему подходу `ShopCategory` / `ShopProduct`.
- На этом этапе не реализовывать код доработок, только анализ и постановку задач.
