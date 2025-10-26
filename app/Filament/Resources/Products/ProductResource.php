<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'Товар';

    protected static string|null|\UnitEnum $navigationGroup = 'Каталог';
    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'Товар';

    protected static ?string $pluralModelLabel = 'Товары';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
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
                            ->options(ProductCategory::all()->pluck('name', 'id'))
                            ->required(),

                        TextInput::make('brand')
                            ->label('Бренд')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->required(),

                        Select::make('color')
                            ->label('Цвет')
                            ->options([
                                'золотой' => 'Золотой',
                                'серебряный' => 'Серебряный',
                                'белый' => 'Белый',
                                'черный' => 'Черный',
                                'красный' => 'Красный',
                            ])
                            ->searchable(),

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

                        Select::make('zodiac_sign')
                            ->label('Знак зодиака')
                            ->options([
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
                            ])
                            ->multiple()
                            ->searchable()
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('brand')->label('Бренд')->searchable()->sortable(),
                TextColumn::make('price')->label('Цена')->money('rub'),
                TextColumn::make('color')->label('Цвет'),
                TextColumn::make('composition')->label('Состав'),
                TextColumn::make('inlay')->label('Вставка'),
                TextColumn::make('zodiac_sign')->label('Знак зодиака'),
            ])
            ->filters([
                Filter::make('has_inlay')
                    ->label('С вставкой')
                    ->query(fn($query) => $query->whereNotNull('inlay')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
