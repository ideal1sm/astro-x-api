<?php

namespace App\Filament\Resources\Shared;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

class ProductCatalogResourceSchema
{
    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $categoryModel
     */
    public static function form(string $categoryModel): array
    {
        return [
            Section::make('Основная информация')
                ->schema([
                    TextInput::make('name')
                        ->label('Наименование')
                        ->placeholder('Кольцо 25 карат')
                        ->required()
                        ->maxLength(255),

                    Select::make('category_id')
                        ->label('Категория')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->options($categoryModel::all()->pluck('name', 'id'))
                        ->required(),

                    TextInput::make('brand')
                        ->label('Бренд')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('price')
                        ->label('Цена')
                        ->numeric()
                        ->required(),

                    TextInput::make('color')
                        ->label('Цвет'),

                    TextInput::make('composition')
                        ->label('Состав')
                        ->placeholder('например: серебро 925'),

                    TextInput::make('inlay')
                        ->label('Вставка')
                        ->placeholder('например: фианит'),

                    TextInput::make('lock_type')
                        ->label('Вид замка')
                        ->placeholder('например: английский замок'),

                    TextInput::make('length')
                        ->label('Длина (см)')
                        ->numeric()
                        ->minValue(0),

                    TextInput::make('production')
                        ->label('Производство')
                        ->placeholder('например: Россия'),

                    Textarea::make('description')
                        ->label('Описание')
                        ->placeholder('например: Точно вам подойдет!'),

                    Textarea::make('short_description')
                        ->label('Короткое описание')
                        ->maxLength(1000),

                    Select::make('zodiac_signs')
                        ->label('Знак зодиака')
                        ->options(self::zodiacOptions())
                        ->multiple()
                        ->searchable(),
                ])
                ->columns(2),
        ];
    }

    public static function tableColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('name')->label('Наименование')->sortable(),
            TextColumn::make('brand')->label('Бренд')->searchable()->sortable(),
            TextColumn::make('price')->label('Цена')->money('rub'),
            TextColumn::make('color')->label('Цвет'),
            TextColumn::make('composition')->label('Состав'),
            TextColumn::make('inlay')->label('Вставка'),
            TextColumn::make('zodiac_signs')->label('Знаки зодиака')->getStateUsing(function ($record) {
                $map = self::zodiacOptions();

                return implode(', ', array_map(
                    fn ($eng) => $map[$eng] ?? $eng,
                    (array) ($record->zodiac_signs ?? []),
                ));
            }),
        ];
    }

    public static function tableFilters(): array
    {
        return [
            Filter::make('has_inlay')
                ->label('С вставкой')
                ->query(fn ($query) => $query->whereNotNull('inlay')),
        ];
    }

    private static function zodiacOptions(): array
    {
        return [
            'aries' => 'Овен',
            'taurus' => 'Телец',
            'gemini' => 'Близнецы',
            'cancer' => 'Рак',
            'leo' => 'Лев',
            'virgo' => 'Дева',
            'libra' => 'Весы',
            'scorpio' => 'Скорпион',
            'sagittarius' => 'Стрелец',
            'capricorn' => 'Козерог',
            'aquarius' => 'Водолей',
            'pisces' => 'Рыбы',
        ];
    }
}
