<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Created    = 'created';
    case InProgress = 'in_progress';
    case Shipped    = 'shipped';
    case Completed  = 'completed';
    case Canceled   = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Created    => 'Оформлен',
            self::InProgress => 'Принят в работу',
            self::Shipped    => 'Передан в доставку',
            self::Completed  => 'Выполнен',
            self::Canceled   => 'Отменён',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
