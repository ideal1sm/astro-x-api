<?php

namespace App\Enums;

enum ProductAvailabilityStatus: string
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case Preorder = 'preorder';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'В наличии',
            self::OutOfStock => 'Нет в наличии',
            self::Preorder => 'Под заказ',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
