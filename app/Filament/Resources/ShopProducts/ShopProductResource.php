<?php

namespace App\Filament\Resources\ShopProducts;

use App\Filament\Resources\ShopProducts\Pages\CreateShopProduct;
use App\Filament\Resources\ShopProducts\Pages\EditShopProduct;
use App\Filament\Resources\ShopProducts\Pages\ListShopProducts;
use App\Filament\Resources\ShopProducts\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Shared\ProductCatalogResourceSchema;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShopProductResource extends Resource
{
    protected static ?string $model = ShopProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|null|\UnitEnum $navigationGroup = 'Мёд';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'Товар Мёд';

    protected static ?string $pluralModelLabel = 'Товары Мёд';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(ProductCatalogResourceSchema::form(ShopCategory::class));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ProductCatalogResourceSchema::tableColumns())
            ->filters(ProductCatalogResourceSchema::tableFilters());
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListShopProducts::route('/'),
            'create' => CreateShopProduct::route('/create'),
            'edit'   => EditShopProduct::route('/{record}/edit'),
        ];
    }
}
