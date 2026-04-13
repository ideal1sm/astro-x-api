<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Users\RelationManagers\ShopOrdersRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action as TableAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|null|\UnitEnum $navigationGroup = 'Общее';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'Пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи (общие аккаунты)';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Основные данные')
                ->schema([
                    TextInput::make('id')
                        ->label('ID')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(User::class, 'email', ignoreRecord: true),

                    TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Статус аккаунта')
                ->schema([
                    TextInput::make('email_verified_at')
                        ->label('Email подтверждён')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Не подтверждён'),

                    TextInput::make('created_at')
                        ->label('Дата регистрации')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('updated_at')
                        ->label('Последнее обновление')
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(3),

            // Пароль намеренно не включён в форму.
            // Для сброса пароля используется API: POST /api/v1/auth/password/forgot
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

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('email_verified_at')
                    ->label('Email')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null)
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::ExclamationCircle)
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (User $record): string => $record->email_verified_at !== null
                        ? 'Подтверждён: ' . $record->email_verified_at->format('d.m.Y H:i')
                        : 'Не подтверждён'
                    ),

                TextColumn::make('shop_orders_count')
                    ->label('Заказов Мёд')
                    ->counts('shopOrders')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('verified')
                    ->label('Email подтверждён')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

                Filter::make('unverified')
                    ->label('Email не подтверждён')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('email_verified_at')),

                Filter::make('has_shop_orders')
                    ->label('Есть заказы Мёд')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->has('shopOrders')),
            ])
            ->recordActions([
                // Административное подтверждение email.
                // Выставляет email_verified_at = now() без уведомлений.
                TableAction::make('verify_email')
                    ->label('Подтвердить email')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->email_verified_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Подтвердить email вручную')
                    ->modalDescription('Это административная операция. Email будет помечен как подтверждённый.')
                    ->action(fn (User $record) => $record->update(['email_verified_at' => now()])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ShopOrdersRelationManager::class,
            AddressesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit'  => EditUser::route('/{record}/edit'),
        ];
    }
}
