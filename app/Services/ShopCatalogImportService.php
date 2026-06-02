<?php

namespace App\Services;

use App\Enums\ProductAvailabilityStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShopCategory;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShopCatalogImportService
{
    public function preview(): array
    {
        $source = $this->sourceCounts();
        $target = $this->targetCounts();

        return [
            'source' => $source,
            'target' => $target,
            'target_has_data' => $this->hasTargetCatalogData($target),
        ];
    }

    public function execute(bool $truncate = false): array
    {
        return DB::transaction(function () use ($truncate): array {
            $source = $this->sourceCounts();
            $targetBefore = $this->targetCounts();
            $targetHasData = $this->hasTargetCatalogData($targetBefore);

            if ($targetHasData && ! $truncate) {
                throw new RuntimeException(
                    'Таблицы shop_* уже содержат данные. Повторный импорт без очистки запрещен. ' .
                    'Запустите команду с опцией --truncate, если каталог магазина нужно пересобрать заново.'
                );
            }

            if ($targetHasData && $truncate) {
                $this->guardNoOrdersBeforeTruncate();
                $this->truncateTargetCatalog();
            }

            $categoryMap = [];
            $importedCategories = 0;
            $importedProducts = 0;
            $importedImages = 0;

            ProductCategory::query()
                ->orderBy('id')
                ->each(function (ProductCategory $sourceCategory) use (&$categoryMap, &$importedCategories): void {
                    $targetCategory = ShopCategory::create([
                        'name' => $sourceCategory->name,
                        'slug' => $sourceCategory->slug,
                        'description' => $sourceCategory->description,
                        'show_on_home' => $sourceCategory->show_on_home,
                        'show_in_catalog' => $sourceCategory->show_in_catalog,
                        'created_at' => $sourceCategory->created_at,
                        'updated_at' => $sourceCategory->updated_at,
                    ]);

                    $categoryMap[$sourceCategory->id] = $targetCategory->id;
                    $importedCategories++;
                });

            Product::query()
                ->with('images')
                ->orderBy('id')
                ->chunk(100, function ($products) use ($categoryMap, &$importedProducts, &$importedImages): void {
                    foreach ($products as $sourceProduct) {
                        $targetProduct = ShopProduct::create([
                            'category_id' => $sourceProduct->category_id !== null
                                ? ($categoryMap[$sourceProduct->category_id] ?? null)
                                : null,
                            'name' => $sourceProduct->name,
                            'short_description' => $sourceProduct->short_description,
                            'zodiac_signs' => $sourceProduct->zodiac_signs,
                            'color' => $sourceProduct->color,
                            'composition' => $sourceProduct->composition,
                            'price' => $sourceProduct->price,
                            'inlay' => $sourceProduct->inlay,
                            'lock_type' => $sourceProduct->lock_type,
                            'length' => $sourceProduct->length,
                            'production' => $sourceProduct->production,
                            'brand' => $sourceProduct->brand,
                            'description' => $sourceProduct->description,
                            'availability_status' => ProductAvailabilityStatus::InStock,
                            'stock_quantity' => null,
                            'created_at' => $sourceProduct->created_at,
                            'updated_at' => $sourceProduct->updated_at,
                        ]);

                        $importedProducts++;

                        foreach ($sourceProduct->images as $sourceImage) {
                            ShopProductImage::create([
                                'shop_product_id' => $targetProduct->id,
                                'path' => $sourceImage->path,
                                'created_at' => $sourceImage->created_at,
                                'updated_at' => $sourceImage->updated_at,
                            ]);

                            $importedImages++;
                        }
                    }
                });

            return [
                'source' => $source,
                'target_before' => $targetBefore,
                'target_after' => $this->targetCounts(),
                'truncate_applied' => $targetHasData && $truncate,
                'imported' => [
                    'categories' => $importedCategories,
                    'products' => $importedProducts,
                    'images' => $importedImages,
                ],
            ];
        });
    }

    private function sourceCounts(): array
    {
        return [
            'categories' => ProductCategory::query()->count(),
            'products' => Product::query()->count(),
            'images' => DB::table('product_images')->count(),
        ];
    }

    private function targetCounts(): array
    {
        return [
            'categories' => ShopCategory::query()->count(),
            'products' => ShopProduct::query()->count(),
            'images' => DB::table('shop_product_images')->count(),
        ];
    }

    private function hasTargetCatalogData(array $counts): bool
    {
        return $counts['categories'] > 0 || $counts['products'] > 0 || $counts['images'] > 0;
    }

    private function guardNoOrdersBeforeTruncate(): void
    {
        if (ShopOrder::query()->exists() || ShopOrderItem::query()->exists()) {
            throw new RuntimeException(
                'Очистка shop-каталога запрещена: в магазине уже есть заказы или позиции заказов. ' .
                'Сначала нужно отдельно обработать данные заказов, иначе ссылки на shop_product_id будут потеряны.'
            );
        }
    }

    private function truncateTargetCatalog(): void
    {
        DB::table('shop_product_images')->delete();
        DB::table('shop_products')->delete();
        DB::table('shop_categories')->delete();
    }
}
