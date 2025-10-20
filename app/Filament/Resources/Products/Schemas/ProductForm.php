<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('color'),
                TextInput::make('composition'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('inlay'),
                TextInput::make('lock_type'),
                TextInput::make('length'),
                TextInput::make('production'),
                TextInput::make('brand'),
                TextInput::make('zodiac_sign'),
            ]);
    }
}
