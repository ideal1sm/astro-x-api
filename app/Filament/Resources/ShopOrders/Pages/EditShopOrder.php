<?php

namespace App\Filament\Resources\ShopOrders\Pages;

use App\Filament\Resources\ShopOrders\ShopOrderResource;
use Filament\Resources\Pages\EditRecord;

class EditShopOrder extends EditRecord
{
    protected static string $resource = ShopOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
