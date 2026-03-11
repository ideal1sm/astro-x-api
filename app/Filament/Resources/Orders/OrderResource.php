<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|null|\UnitEnum $navigationGroup = 'Магазин';

    protected static ?string $navigationLabel = 'Заказы';

    protected static ?string $modelLabel = 'Заказ';

    protected static ?string $pluralModelLabel = 'Заказы';

    /** Метки статусов заказа для форм и таблиц. */
    private static function statusOptions(): array
    {
        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Статус заказа')
                ->schema([
                    Select::make('status')
                        ->label('Статус')
                        ->options(self::statusOptions())
                        ->required()
                        ->native(false),
                ]),

            Section::make('Информация о заказе')
                ->schema([
                    TextInput::make('id')
                        ->label('ID заказа')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('user.email')
                        ->label('Покупатель')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('total')
                        ->label('Сумма заказа (₽)')
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('notes')
                        ->label('Примечание покупателя')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Покупатель')
                    ->searchable()
                    ->sortable(),

                SelectColumn::make('status')
                    ->label('Статус')
                    ->options(self::statusOptions()),

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
                    ->options(self::statusOptions()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit'  => EditOrder::route('/{record}/edit'),
        ];
    }
}
