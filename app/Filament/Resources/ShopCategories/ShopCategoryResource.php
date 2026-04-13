<?php

namespace App\Filament\Resources\ShopCategories;

use App\Filament\Resources\ShopCategories\Pages\CreateShopCategory;
use App\Filament\Resources\ShopCategories\Pages\EditShopCategory;
use App\Filament\Resources\ShopCategories\Pages\ListShopCategories;
use App\Models\ShopCategory;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShopCategoryResource extends Resource
{
    protected static ?string $model = ShopCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static string|null|\UnitEnum $navigationGroup = 'Мёд';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Категории';

    protected static ?string $pluralLabel = 'Категории Мёд';

    protected static ?string $modelLabel = 'Категория Мёд';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Основная информация')
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('slug')
                        ->label('Slug')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Генерируется автоматически из названия'),

                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Отображение')
                ->schema([
                    Toggle::make('show_on_home')
                        ->label('Выводить на главной')
                        ->onColor('success')
                        ->default(false),

                    Toggle::make('show_in_catalog')
                        ->label('Выводить в каталоге')
                        ->onColor('success')
                        ->default(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Название')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray'),

                IconColumn::make('show_on_home')
                    ->label('Главная')
                    ->boolean()
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle)
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('show_in_catalog')
                    ->label('Каталог')
                    ->boolean()
                    ->trueIcon(Heroicon::CheckCircle)
                    ->falseIcon(Heroicon::XCircle)
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('show_on_home')
                    ->label('Только на главной')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('show_on_home', true)),

                Filter::make('show_in_catalog')
                    ->label('Только в каталоге')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where('show_in_catalog', true)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListShopCategories::route('/'),
            'create' => CreateShopCategory::route('/create'),
            'edit'   => EditShopCategory::route('/{record}/edit'),
        ];
    }
}
