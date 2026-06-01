# Синхронизация Swagger/OpenAPI

**Приоритет:** P1

## Контекст

ТЗ прямо требует синхронизации документации. В проекте уже есть `openapi.json`, но после доработок checkout/payments/delivery он неизбежно устареет.

## Что найдено в проекте

- OpenAPI хранится вручную в `openapi.json`.
- В проекте нет найденной генерации схем из кода.
- Текущее описание уже отражает разделение Astro-x и Мёд, но не покрывает новые требования.

## Что нужно сделать

1. После каждой API-задачи обновлять `openapi.json`.
2. Синхронизировать:
  - guest checkout;
  - delivery payload;
  - payment fields;
  - stock fields;
  - новые ошибки и статусы;
  - при необходимости webhook endpoints.
3. Удалить из документации ожидания backend-интеграции со СДЭК.
4. Зафиксировать, что storefront команды получают API contract из `openapi.json`.

## Backend

- Поддерживать фактическое соответствие между `FormRequest`, `Resource`, `routes/api.php` и `openapi.json`.

## Admin

- Не относится напрямую.

## Frontend

- Frontend вне репозитория.
- `openapi.json` должен стать источником актуального контракта для checkout и личного кабинета магазина.

## Database

- Не относится напрямую.

## API

- Задача охватывает все изменённые shop endpoints.

## Swagger/OpenAPI

- Центральный артефакт задачи: `openapi.json`.
- При необходимости дополнительно актуализировать `API.md` или удалить устаревшие фрагменты, если он остаётся в проекте как вспомогательный файл.

## Notifications

- В документации можно кратко отразить event effects, если это полезно, но без избыточной детализации внутренних интеграций.

## Tests

- Smoke-проверка, что примеры и обязательные поля в `openapi.json` соответствуют реальной валидации.
- Если появятся contract tests, связать их с этой задачей.

## Acceptance criteria

- `openapi.json` отражает фактические request/response схемы.
- Документация не содержит ложного backend-сценария со СДЭК.
- Фронт и backend опираются на один и тот же контракт.

## Важные ограничения

- Не оставлять расхождения между кодом и документацией.
- Не описывать в OpenAPI несуществующие endpoints и интеграции.

---

# Implementation status

## Status

Completed

## Implementation date

2026-06-01

## Changed files

- openapi.json
- tests/Unit/OpenApiContractTest.php
- docs/tasks/15-openapi-sync.md
- docs/tasks/README.md

## Database changes

- Нет изменений схемы БД

## API changes

- Runtime API не менялся
- Синхронизирован только документированный контракт `openapi.json`

## Admin changes

- Нет изменений админки

## Swagger/OpenAPI

- Актуализирован `openapi.json` под фактический shop-контракт
- В `ShopProductShort` добавлено отсутствовавшее поле `color`
- В `POST /shop/orders` description зафиксировано:
  - отсутствие backend-интеграции со СДЭК
  - side effects в виде внутренних email/Telegram-уведомлений менеджерам
- Подтверждено наличие актуальных полей для:
  - guest checkout
  - delivery payload
  - payment fields
  - stock fields
  - webhook ЮKassa

## Tests

- Добавлен `tests/Unit/OpenApiContractTest.php`
- Проверяется:
  - `openapi.json` валиден как JSON
  - checkout contract содержит фактические обязательные поля
  - order response schema содержит payment/delivery/consent поля
  - product schema содержит stock и `color`
  - OpenAPI не описывает несуществующую backend-интеграцию `cdek`

## Manual verification

- Открыть `openapi.json` и сверить `POST /shop/orders` с `CreateShopOrderRequest`
- Сверить `ShopOrderFull` с `ShopOrderFullResource`
- Сверить `ShopProductShort` с `ShopProductShortResource`
- Проверить, что в shop checkout description нет ложного сценария backend-интеграции со СДЭК

## Notes

- Задача закрывает именно синхронизацию документации, а не изменение runtime API.
- `API.md` не менялся: в рамках этой задачи источником актуального shop-контракта закреплён `openapi.json`.
- Автотесты и `pint` в текущем окружении не запускаются из-за отсутствующего PHP extension `mbstring`.
