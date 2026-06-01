<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storefront URLs
    |--------------------------------------------------------------------------
    |
    | Frontend приложения магазина в этом репозитории нет. Если менеджерам
    | нужна ссылка из админки на публичную карточку товара, задайте шаблон URL.
    | Доступные плейсхолдеры: {id}, {category_slug}.
    |
    */
    'product_url_pattern' => env('SHOP_STOREFRONT_PRODUCT_URL_PATTERN'),

    'notifications' => [
        'email' => [
            'enabled' => env('SHOP_ORDER_EMAIL_NOTIFICATIONS_ENABLED', true),
            'recipients' => array_values(array_filter(array_map(
                static fn (string $email): string => trim($email),
                explode(',', (string) env('SHOP_ORDER_NOTIFICATION_EMAILS', ''))
            ))),
        ],
        'telegram' => [
            'enabled' => env('SHOP_ORDER_TELEGRAM_NOTIFICATIONS_ENABLED', true),
            'bot_token' => env('SHOP_TELEGRAM_BOT_TOKEN'),
            'chat_ids' => array_values(array_filter(array_map(
                static fn (string $chatId): string => trim($chatId),
                explode(',', (string) env('SHOP_TELEGRAM_CHAT_IDS', ''))
            ))),
        ],
    ],
];
