# Чеклист тестирования доработок магазина

**Приоритет:** P1

## Контекст

ТЗ требует тестов. В проекте уже есть feature-тесты каталога, профиля, auth и заказов, но покрытие для будущих магазинных доработок нужно расширить системно.

## Что найдено в проекте

- Feature-тесты магазина уже есть: `tests/Feature/ShopApiTest.php`, `tests/Feature/ShopCatalogStructureTest.php`.
- Исторические тесты заказов есть для Astro-x сущностей: `tests/Feature/Orders/OrderTest.php`.
- Отдельных тестов на оплату, доставку, уведомления и админку нет.

## Что нужно сделать

1. Зафиксировать обязательный набор тестов по каждому блоку.
2. Распределить тесты по уровням:
  - feature API;
  - unit/domain services;
  - integration с mocked external APIs;
  - при необходимости admin/UI smoke tests.
3. Добавить регрессионные тесты на разделение Astro-x и Мёд.

## Backend

- Создать/расширить test suites для:
  - guest checkout;
  - доставка;
  - остатки;
  - ЮKassa;
  - email;
  - Telegram;
  - OpenAPI contract consistency.

## Admin

- Минимум smoke-проверки критичных экранов заказа, если в проекте принята практика admin-тестов.

## Frontend

- Frontend вне репозитория.
- Для внешней команды зафиксировать отдельный QA checklist:
  - checkout;
  - legal links;
  - success page;
  - 404;
  - mobile photo behavior.

## Database

- Проверять миграции на новые nullable/non-nullable поля, дефолты, корректность JSON/decimal полей.

## API

- Проверять envelope, validation errors, авторизацию, статусы и response payload.

## Swagger/OpenAPI

- Проверять, что обязательные поля документации соответствуют реальному API.

## Notifications

- Fake/Mock mail and HTTP tests.
- Проверка, что ошибки уведомлений не ломают заказ.

## Tests

1. Guest checkout success/fail.
2. Delivery payload validation and persistence.
3. Stock validation and decrement.
4. Payment creation and webhook processing.
5. Admin-visible order data integrity.
6. Email notification dispatch.
7. Telegram notification dispatch.
8. Separation of `shop_*` and Astro-x entities.
9. OpenAPI sync smoke assertions.

## Acceptance criteria

- Для каждого P1-блока есть тестовый сценарий успеха и ошибки.
- Критические бизнес-правила покрыты автоматизированно.
- Регресс по разделению каталогов отслеживается тестами.

## Важные ограничения

- Не ограничиваться happy path.
- Не тестировать внешние сервисы без mock/fake.
