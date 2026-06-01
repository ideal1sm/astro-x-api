# Увеличение изображения товара в заказе в админке

**Приоритет:** P1

## Контекст

Блок ТЗ: в заказе администратор должен быстро открыть крупное изображение заказанного товара без перехода со страницы заказа.

## Что найдено в проекте

- Позиции заказа в админке сейчас выводятся только текстом: `app/Filament/Resources/ShopOrders/RelationManagers/ItemsRelationManager.php`.
- У товаров магазина есть связь `images`: `app/Models/ShopProduct.php`, `app/Models/ShopProductImage.php`.
- В сервисе заказа eager-loading изображений уже есть: `app/Services/CreateShopOrderService.php`.

## Что нужно сделать

1. Добавить миниатюру товара в позиции заказа.
2. Сделать миниатюру кликабельной.
3. Открывать превью в modal/lightbox без ухода со страницы заказа.
4. Продумать fallback без изображения.
5. По возможности поддержать переключение между несколькими изображениями товара.

## Backend

- Убедиться, что админка получает нужные URL изображений товаров.
- При необходимости добавить accessor/preview field для первого изображения.

## Admin

- Изменить `ItemsRelationManager`.
- Добавить колонку миниатюры и action/modal preview.
- Добавить ссылку в карточку товара в админке.
- Проверить базовую mobile-адаптацию Filament view.

## Frontend

- Пользовательский storefront не затрагивается.

## Database

- Новые таблицы не требуются.

## API

- Публичный API обычно не требуется менять, если задача ограничена админкой.

## Swagger/OpenAPI

- Изменения не обязательны.

## Notifications

- Не относится напрямую, но повторно использует товарные изображения.

## Tests

- Проверка наличия изображения у позиции заказа.
- Проверка fallback при отсутствии изображения.
- При наличии admin/UI тестов проверить открытие modal preview.

## Acceptance criteria

- В заказе видна миниатюра товара.
- По клику открывается увеличенное фото.
- Отсутствие фото обрабатывается без ошибок.

## Важные ограничения

- Не дублировать изображения в заказе без необходимости, если можно безопасно читать их из товара.
- Не ухудшать производительность страницы заказа чрезмерной загрузкой full-size изображений.

---

# Implementation status

## Status

Completed

## Implementation date

2026-06-01

## Changed files

- app/Filament/Resources/ShopOrders/RelationManagers/ItemsRelationManager.php
- app/Models/ShopProduct.php
- resources/views/filament/shop-orders/item-image-preview.blade.php
- tests/Unit/ShopProductPreviewImagesTest.php
- docs/tasks/08-admin-order-product-image-preview.md
- docs/tasks/README.md

## Database changes

- Нет изменений схемы БД

## API changes

- Внешние API не изменялись

## Admin changes

- В позициях заказа Мёд добавлена миниатюра товара
- Миниатюра стала кликабельной и открывает modal preview без ухода со страницы заказа
- В modal preview:
  - показывается крупное первое изображение товара
  - при наличии нескольких фото выводится дополнительная gallery-сетка
  - отсутствие фото обрабатывается fallback-сообщением без ошибки
- Сохранены переходы к товару в админке и на storefront из relation manager

## Swagger/OpenAPI

- Изменения не требовались

## Tests

- Добавлен unit-тест `tests/Unit/ShopProductPreviewImagesTest.php`
- Проверяется:
  - primary image URL для товара с изображениями
  - список preview image URLs для товара с несколькими изображениями
  - fallback без изображений

## Manual verification

- Открыть заказ Мёд в Filament и убедиться, что у позиции отображается миниатюра
- Нажать на миниатюру и проверить открытие modal preview
- Проверить товар с несколькими изображениями: в modal видны дополнительные фото
- Проверить товар без изображения: вместо падения интерфейса показывается fallback

## Notes

- Изображения не дублируются в `shop_order_items`; preview строится из связанного `ShopProduct`.
- Frontend storefront в этой задаче не затрагивался.
- Автотесты и `pint` в текущем окружении не запускаются из-за отсутствующего PHP extension `mbstring`.
