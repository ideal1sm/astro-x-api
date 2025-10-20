<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Изображения';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            FileUpload::make('path')
                ->label('Файл')
                ->image()
                ->directory('products')
                ->visibility('public')
                ->disk(env('FILESYSTEM_DISK'))
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')->label('Изображение')->disk('local'),
                TextColumn::make('created_at')->dateTime()->label('Добавлено'),
            ])
            ->headerActions([
                CreateAction::make()->label('Добавить изображение'),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
