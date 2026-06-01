<?php

namespace App\Filament\Resources\Shared;

use App\Enums\ProductAvailabilityStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class ProductCatalogResourceSchema
{
    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $categoryModel
     */
    public static function form(string $categoryModel, bool $withStock = false): array
    {
        $schema = [
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

        if ($withStock) {
            $schema[] = Section::make('Наличие')
                ->schema([
                    Select::make('availability_status')
                        ->label('Статус наличия')
                        ->options(self::availabilityOptions())
                        ->required()
                        ->native(false)
                        ->default(ProductAvailabilityStatus::InStock->value),

                    TextInput::make('stock_quantity')
                        ->label('Остаток')
                        ->numeric()
                        ->minValue(0)
                        ->nullable()
                        ->helperText('Оставьте пустым, если количественный учёт не используется. Для "В наличии" значение 0 будет автоматически интерпретировано как "Нет в наличии".'),
                ])
                ->columns(2);
        }

        return $schema;
    }

    public static function tableColumns(bool $withStock = false): array
    {
        $columns = [
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

        if ($withStock) {
            $columns[] = TextColumn::make('availability_status')
                ->label('Наличие')
                ->badge()
                ->formatStateUsing(fn (ProductAvailabilityStatus|string $state) => $state instanceof ProductAvailabilityStatus
                    ? $state->label()
                    : (self::availabilityOptions()[$state] ?? $state)
                )
                ->color(fn (ProductAvailabilityStatus|string $state) => match ($state instanceof ProductAvailabilityStatus ? $state->value : $state) {
                    ProductAvailabilityStatus::InStock->value => 'success',
                    ProductAvailabilityStatus::OutOfStock->value => 'danger',
                    ProductAvailabilityStatus::Preorder->value => 'warning',
                    default => 'gray',
                });

            $columns[] = TextColumn::make('stock_quantity')
                ->label('Остаток')
                ->placeholder('Без учёта');
        }

        return $columns;
    }

    public static function tableFilters(bool $withStock = false): array
    {
        $filters = [
            Filter::make('has_inlay')
                ->label('С вставкой')
                ->query(fn ($query) => $query->whereNotNull('inlay')),
        ];

        if ($withStock) {
            $filters[] = SelectFilter::make('availability_status')
                ->label('Наличие')
                ->options(self::availabilityOptions());
        }

        return $filters;
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

    private static function availabilityOptions(): array
    {
        return collect(ProductAvailabilityStatus::cases())
            ->mapWithKeys(fn (ProductAvailabilityStatus $status) => [$status->value => $status->label()])
            ->all();
    }
}
