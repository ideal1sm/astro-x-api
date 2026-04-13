<?php

namespace App\Filament\Resources\ShopOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Позиции заказа';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('Кол-во'),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB'),

                TextColumn::make('total')
                    ->label('Итого')
                    ->money('RUB'),
            ])
            ->paginated(false)
            ->recordActions([])
            ->toolbarActions([]);
    }
}
