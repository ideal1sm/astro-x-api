<?php

namespace App\Filament\Resources\ShopOrders\RelationManagers;

use App\Filament\Resources\ShopProducts\ShopProductResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Позиции заказа';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['product.images', 'product.category']))
            ->columns([
                ImageColumn::make('product_image')
                    ->label('Фото')
                    ->disk(env('FILESYSTEM_DISK'))
                    ->defaultImageUrl(self::fallbackImageDataUri())
                    ->getStateUsing(fn ($record) => $record->product?->images->first()?->path)
                    ->action(
                        Action::make('previewImage')
                            ->label('Просмотр изображения')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->modalHeading(fn ($record) => $record->product?->name ?: 'Изображение товара')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Закрыть')
                            ->modalContent(fn ($record): View => view('filament.shop-orders.item-image-preview', [
                                'productName' => $record->product?->name,
                                'images' => $record->product?->preview_image_urls ?? [],
                            ]))
                    ),

                TextColumn::make('product.name')
                    ->label('Товар')
                    ->searchable()
                    ->url(fn ($record) => $record->product ? ShopProductResource::getUrl('edit', ['record' => $record->product]) : null)
                    ->openUrlInNewTab(),

                TextColumn::make('product.id')
                    ->label('ID товара')
                    ->sortable(),

                TextColumn::make('product.category.name')
                    ->label('Категория')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Кол-во')
                    ->alignCenter(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB'),

                TextColumn::make('total')
                    ->label('Итого')
                    ->money('RUB'),

                TextColumn::make('product.storefront_url')
                    ->label('Сайт')
                    ->formatStateUsing(fn ($state) => $state ? 'Открыть' : 'Не настроено')
                    ->url(fn ($state) => $state ?: null)
                    ->openUrlInNewTab()
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),
            ])
            ->paginated(false)
            ->recordActions([
                Action::make('editProduct')
                    ->label('Товар в админке')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn ($record) => $record->product ? ShopProductResource::getUrl('edit', ['record' => $record->product]) : null)
                    ->openUrlInNewTab(),
                Action::make('openStorefront')
                    ->label('Товар на сайте')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn ($record) => $record->product?->storefront_url)
                    ->visible(fn ($record) => filled($record->product?->storefront_url))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([]);
    }

    private static function fallbackImageDataUri(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120" fill="none">
  <rect width="120" height="120" rx="16" fill="#F5F5F4"/>
  <path d="M35 80L50 62L61 72L76 54L90 80H35Z" fill="#D6D3D1"/>
  <circle cx="48" cy="42" r="8" fill="#D6D3D1"/>
</svg>
SVG;

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}
