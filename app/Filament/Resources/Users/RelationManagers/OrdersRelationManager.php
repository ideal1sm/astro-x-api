<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Заказы пользователя';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        $statusOptions = collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])
            ->all();

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (string $state) => $statusOptions[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'created'     => 'info',
                        'in_progress' => 'warning',
                        'shipped'     => 'primary',
                        'completed'   => 'success',
                        'canceled'    => 'danger',
                        default       => 'gray',
                    }),

                TextColumn::make('total')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Позиций')
                    ->counts('items'),

                TextColumn::make('created_at')
                    ->label('Оформлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options($statusOptions),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
