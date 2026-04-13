<?php

namespace App\Filament\Resources\ShopProducts\Pages;

use App\Filament\Resources\ShopProducts\ShopProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShopProducts extends ListRecords
{
    protected static string $resource = ShopProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
