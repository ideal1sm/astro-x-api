<?php

namespace App\Services\Notifications;

use Illuminate\Http\Client\Factory as HttpFactory;

class TelegramBotClient
{
    public function __construct(private readonly HttpFactory $http) {}

    public function sendMessage(string $botToken, string $chatId, array $payload): void
    {
        $this->http
            ->baseUrl("https://api.telegram.org/bot{$botToken}")
            ->asForm()
            ->post('/sendMessage', array_merge($payload, [
                'chat_id' => $chatId,
            ]))
            ->throw();
    }
}
