<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case WaitingForCapture = 'waiting_for_capture';
    case Succeeded = 'succeeded';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает оплаты',
            self::WaitingForCapture => 'Ожидает подтверждения',
            self::Succeeded => 'Оплачен',
            self::Canceled => 'Отменён',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromYookassaStatus(string $status): self
    {
        return match ($status) {
            'pending' => self::Pending,
            'waiting_for_capture' => self::WaitingForCapture,
            'succeeded' => self::Succeeded,
            'canceled' => self::Canceled,
            default => self::Pending,
        };
    }
}
