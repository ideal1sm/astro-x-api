<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Yookassa = 'yookassa';

    public function label(): string
    {
        return match ($this) {
            self::Yookassa => 'ЮKassa',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
