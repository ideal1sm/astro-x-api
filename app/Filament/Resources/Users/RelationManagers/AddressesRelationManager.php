<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Адреса доставки';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->placeholder('—'),

                TextColumn::make('city')
                    ->label('Город'),

                TextColumn::make('street')
                    ->label('Улица'),

                TextColumn::make('postal_code')
                    ->label('Индекс'),

                IconColumn::make('is_default')
                    ->label('По умолчанию')
                    ->boolean()
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle)
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
