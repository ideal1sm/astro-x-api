<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Подтверждение email')
            ->line('Для подтверждения email передайте токен в POST /api/v1/auth/email/confirm')
            ->line('Токен: ' . $this->token)
            ->line('Токен действителен 24 часа.');
    }
}
