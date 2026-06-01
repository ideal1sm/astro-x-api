# Индекс задач по доработке jewelry-med.ru

## Таблица задач

| № | Файл | Название | Приоритет | Зависимости | Status | Краткое описание |
|---|------|----------|-----------|-------------|--------|------------------|
| 00 | `00-project-context.md` | Контекст проекта и архитектуры | P1 | - | Completed | Карта текущего проекта, стек, сущности и ограничения архитектуры |
| 01 | `01-guest-checkout.md` | Гостевое оформление заказа | P1 | 00 | Completed | Перевод checkout магазина на сценарий без обязательной авторизации |
| 02 | `02-delivery-data-without-cdek-integration.md` | Сохранение данных доставки, переданных с фронта | P1 | 00, 01 | Completed | Новая модель доставки без интеграции со СДЭК |
| 03 | `03-product-stock.md` | Остатки товаров магазина | P1 | 00, 01 | Completed | Управление наличием и запрет заказа при отсутствии остатка |
| 04 | `04-yookassa-payments.md` | Интеграция оплаты через ЮKassa | P1 | 00, 01, 02 | Completed | Платежи, статусы оплаты, webhook и возвраты пользователя |
| 05 | `05-orders-admin.md` | Расширение заказов в админке | P1 | 01, 02, 04 | Completed | Полный просмотр заказа в Filament для менеджеров |
| 06 | `06-order-email-notifications.md` | Email-уведомления о новых заказах | P1 | 01, 02, 04, 05 | Completed | Письмо менеджерам с данными заказа |
| 07 | `07-order-telegram-notifications.md` | Telegram-уведомления о новых заказах | P1 | 01, 02, 04, 05 | Completed | Telegram-сообщение менеджерам о новом заказе |
| 08 | `08-admin-order-product-image-preview.md` | Увеличение изображения товара в заказе в админке | P1 | 05 | Completed | Preview фото товара прямо из заказа |
| 09 | `09-legal-pages.md` | Политика конфиденциальности | P1 | 00 | Not started | Страница политики и ссылки на неё |
| 10 | `10-personal-data-consent.md` | Согласие на обработку персональных данных | P1 | 01, 09 | Blocked | Обязательное согласие в checkout и contract для остальных форм |
| 11 | `11-shop-info-pages.md` | Правила покупки, оплаты, доставки и возврата | P2 | 00 | Not started | Информационные страницы для покупателей |
| 12 | `12-not-found-page.md` | Страница 404 | P2 | 00 | Not started | Пользовательская 404 с корректным HTTP-статусом |
| 13 | `13-mobile-product-image-improvement.md` | Улучшение фото товара в мобильной карточке | P2 | 00 | Not started | Frontend-задача на mobile UX карточки товара |
| 14 | `14-order-success-page.md` | Страница успешного оформления заказа | P2 | 01, 02, 04 | Not started | Success page после оформления/оплаты |
| 15 | `15-openapi-sync.md` | Синхронизация Swagger/OpenAPI | P1 | 01, 02, 03, 04 | Completed | Актуализация `openapi.json` под реальные контракты |
| 16 | `16-tests-checklist.md` | Чеклист тестирования доработок магазина | P1 | 01, 02, 03, 04, 05, 06, 07, 15 | Not started | Обязательное тестовое покрытие ключевых блоков |
| 17 | `17-wildberries-pvz-research.md` | Research: возможность доставки в ПВЗ Wildberries | P3 | 00 | Not started | Отдельное исследование без реализации |

## Рекомендуемый порядок реализации

1. `00-project-context.md`
2. `01-guest-checkout.md`
3. `02-delivery-data-without-cdek-integration.md`
4. `03-product-stock.md`
5. `04-yookassa-payments.md`
6. `05-orders-admin.md`
7. `08-admin-order-product-image-preview.md`
8. `06-order-email-notifications.md`
9. `07-order-telegram-notifications.md`
10. `09-legal-pages.md`
11. `10-personal-data-consent.md`
12. `15-openapi-sync.md`
13. `16-tests-checklist.md`
14. `14-order-success-page.md`
15. `11-shop-info-pages.md`
16. `12-not-found-page.md`
17. `13-mobile-product-image-improvement.md`
18. `17-wildberries-pvz-research.md`

## Короткая карта зависимостей

- Базовый контекст: `00`
- Checkout foundation: `01`
- Delivery model: `02` зависит от `01`
- Stock: `03` зависит от структуры заказа, но может идти параллельно с `02` после фиксации payload заказа
- Payments: `04` зависит от `01` и `02`
- Admin/notifications: `05`, `06`, `07`, `08` зависят от полноты данных заказа
- Legal/consent: `09` и `10` связаны между собой
- Success page: `14` зависит от checkout/payment flow
- Docs/tests: `15`, `16` синхронизируются после основных P1-изменений
- Research: `17` изолирована и не блокирует запуск
