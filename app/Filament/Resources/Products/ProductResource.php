<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Shared\ProductCatalogResourceSchema;
use App\Models\Product;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'Товар';

    protected static string|null|\UnitEnum $navigationGroup = 'Astro-x';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'Товар Astro-x';

    protected static ?string $pluralModelLabel = 'Товары Astro-x';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema(ProductCatalogResourceSchema::form(ProductCategory::class));
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
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
