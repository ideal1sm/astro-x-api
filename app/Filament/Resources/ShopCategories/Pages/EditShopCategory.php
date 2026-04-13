<?php

namespace App\Filament\Resources\ShopCategories\Pages;

use App\Filament\Resources\ShopCategories\ShopCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShopCategory extends EditRecord
{
    protected static string $resource = ShopCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
