<?php

namespace App\Filament\Resources\ShopOrders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\ShopOrders\Pages\EditShopOrder;
use App\Filament\Resources\ShopOrders\Pages\ListShopOrders;
use App\Filament\Resources\ShopOrders\RelationManagers\ItemsRelationManager;
use App\Models\ShopOrder;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShopOrderResource extends Resource
{
    protected static ?string $model = ShopOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|null|\UnitEnum $navigationGroup = 'Мёд';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Заказы';

    protected static ?string $modelLabel = 'Заказ Мёд';

    protected static ?string $pluralModelLabel = 'Заказы Мёд';

    private static function statusOptions(): array
    {
        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])
            ->all();
    }

    private static function paymentStatusOptions(): array
    {
        return collect(PaymentStatus::cases())
            ->mapWithKeys(fn (PaymentStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    private static function deliveryMethodOptions(): array
    {
        return ShopOrder::query()
            ->whereNotNull('delivery_method')
            ->distinct()
            ->orderBy('delivery_method')
            ->pluck('delivery_method', 'delivery_method')
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withCount('items');
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

                    Placeholder::make('items_count')
                        ->label('Позиций')
                        ->content(fn (?ShopOrder $record) => (string) ($record?->items()->count() ?? 0)),
                ]),

            Section::make('Информация о заказе')
                ->schema([
                    TextInput::make('id')
                        ->label('ID заказа')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('items_total')
                        ->label('Сумма товаров (₽)')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('total')
                        ->label('Итоговая сумма (₽)')
                        ->disabled()
                        ->dehydrated(false),

                    Placeholder::make('created_at')
                        ->label('Оформлен')
                        ->content(fn (?ShopOrder $record) => $record?->created_at?->format('d.m.Y H:i') ?? '-'),

                    Placeholder::make('updated_at')
                        ->label('Обновлён')
                        ->content(fn (?ShopOrder $record) => $record?->updated_at?->format('d.m.Y H:i') ?? '-'),

                    Textarea::make('notes')
                        ->label('Примечание покупателя')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Оплата')
                ->schema([
                    TextInput::make('payment_method')
                        ->label('Способ оплаты')
                        ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : $state)
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('payment_status')
                        ->label('Статус оплаты')
                        ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : $state)
                        ->disabled()
                        ->dehydrated(false),

                    Placeholder::make('payment_initiated_at')
                        ->label('Платёж создан')
                        ->content(fn (?ShopOrder $record) => $record?->payment_initiated_at?->format('d.m.Y H:i') ?? '-'),

                    TextInput::make('payment_amount')
                        ->label('Сумма оплаты (₽)')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('yookassa_payment_id')
                        ->label('ID платежа ЮKassa')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('payment_paid_at')
                        ->label('Оплачен в')
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('payment_confirmation_url')
                        ->label('Ссылка на оплату')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(2),

                    Textarea::make('payment_failure_reason')
                        ->label('Причина отмены/ошибки оплаты')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(2),

                    Textarea::make('payment_payload')
                        ->label('Payment payload')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null)
                        ->rows(8)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Покупатель')
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Имя')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('customer_phone')
                        ->label('Телефон')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('customer_email')
                        ->label('Email')
                        ->disabled()
                        ->dehydrated(false),

                    Placeholder::make('personal_data_consent_at')
                        ->label('Согласие на ПДн')
                        ->content(fn (?ShopOrder $record) => $record?->personal_data_consent_at?->format('d.m.Y H:i') ?? '-'),

                    TextInput::make('user.email')
                        ->label('Аккаунт')
                        ->placeholder('Гостевой заказ')
                        ->disabled()
                        ->dehydrated(false),

                    Placeholder::make('customer_type')
                        ->label('Тип заказа')
                        ->content(fn (?ShopOrder $record) => $record?->user_id ? 'Пользователь с аккаунтом' : 'Гостевой заказ'),
                ])
                ->columns(2),

            Section::make('Доставка')
                ->schema([
                    TextInput::make('delivery_method')
                        ->label('Способ доставки')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('delivery_price')
                        ->label('Стоимость доставки (₽)')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('delivery_city')
                        ->label('Город доставки')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('recipient_name')
                        ->label('Получатель')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('recipient_phone')
                        ->label('Телефон получателя')
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('delivery_address')
                        ->label('Адрес доставки')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(2),

                    Textarea::make('delivery_pickup_point')
                        ->label('Пункт выдачи')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(2),

                    Textarea::make('delivery_comment')
                        ->label('Комментарий по доставке')
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(2),

                    Textarea::make('delivery_payload')
                        ->label('Delivery payload')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null)
                        ->rows(8)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Служебная информация')
                ->schema([
                    Placeholder::make('account_link_hint')
                        ->label('Аккаунт пользователя')
                        ->content(fn (?ShopOrder $record) => $record?->user_id
                            ? 'Откройте пользователя через раздел "Общее → Пользователи".'
                            : 'У заказа нет связанного аккаунта пользователя.'),
                    Placeholder::make('storefront_link_hint')
                        ->label('Публичные ссылки на товары')
                        ->content(fn () => filled(config('shop.product_url_pattern'))
                            ? 'Ссылки на товар на сайте доступны в позициях заказа.'
                            : 'SHOP_STOREFRONT_PRODUCT_URL_PATTERN не настроен, публичные ссылки на товар скрыты в действиях.'),
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

                TextColumn::make('customer_name')
                    ->label('Покупатель')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (OrderStatus|string $state) => $state instanceof OrderStatus ? $state->label() : (self::statusOptions()[$state] ?? $state))
                    ->badge()
                    ->color(fn (OrderStatus|string $state) => match ($state instanceof OrderStatus ? $state->value : $state) {
                        'created' => 'info',
                        'in_progress' => 'warning',
                        'shipped' => 'primary',
                        'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?: '-'))
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof PaymentStatus ? $state->value : $state) {
                        'pending', 'waiting_for_capture' => 'warning',
                        'succeeded' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('delivery_method')
                    ->label('Доставка')
                    ->toggleable(),

                TextColumn::make('user_id')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => $state ? 'Аккаунт' : 'Гость')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

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
                SelectFilter::make('payment_status')
                    ->label('Статус оплаты')
                    ->options(self::paymentStatusOptions()),
                SelectFilter::make('delivery_method')
                    ->label('Способ доставки')
                    ->options(self::deliveryMethodOptions()),
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
            'index' => ListShopOrders::route('/'),
            'edit'  => EditShopOrder::route('/{record}/edit'),
        ];
    }
}
