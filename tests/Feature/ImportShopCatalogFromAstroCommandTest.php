<?php

namespace Tests\Feature;

use App\Enums\ProductAvailabilityStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ShopCategory;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class ImportShopCatalogFromAstroCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_categories_products_and_images_into_empty_shop_catalog(): void
    {
        $category = ProductCategory::factory()
            ->onHome()
            ->inCatalog()
            ->create([
                'name' => 'Кольца',
                'slug' => 'rings',
                'description' => 'Категория колец',
            ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Кольцо с янтарем',
            'short_description' => 'Краткое описание',
            'price' => '14990.00',
            'color' => 'золотой',
            'composition' => 'золото 585',
            'inlay' => 'янтарь',
            'lock_type' => 'английский замок',
            'length' => '18',
            'production' => 'Россия',
            'brand' => 'MED',
            'zodiac_signs' => ['aries', 'leo'],
            'description' => 'Полное описание',
        ]);

        ProductImage::factory()->create(['product_id' => $product->id, 'path' => 'products/ring-1.jpg']);
        ProductImage::factory()->create(['product_id' => $product->id, 'path' => 'products/ring-2.jpg']);

        $this->artisan('shop:import-astro-catalog')
            ->expectsOutputToContain('Импорт завершен.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('shop_categories', 1);
        $this->assertDatabaseCount('shop_products', 1);
        $this->assertDatabaseCount('shop_product_images', 2);

        $shopCategory = ShopCategory::query()->firstOrFail();
        $shopProduct = ShopProduct::query()->with('images')->firstOrFail();

        $this->assertSame('Кольца', $shopCategory->name);
        $this->assertSame('rings', $shopCategory->slug);
        $this->assertTrue($shopCategory->show_on_home);
        $this->assertTrue($shopCategory->show_in_catalog);

        $this->assertSame($shopCategory->id, $shopProduct->category_id);
        $this->assertSame('Кольцо с янтарем', $shopProduct->name);
        $this->assertSame('Краткое описание', $shopProduct->short_description);
        $this->assertSame('14990.00', (string) $shopProduct->price);
        $this->assertSame(['aries', 'leo'], $shopProduct->zodiac_signs);
        $this->assertSame(ProductAvailabilityStatus::InStock, $shopProduct->availability_status);
        $this->assertNull($shopProduct->stock_quantity);
        $this->assertSame(['products/ring-1.jpg', 'products/ring-2.jpg'], $shopProduct->images->pluck('path')->all());
    }

    public function test_dry_run_does_not_write_to_shop_catalog(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductImage::factory()->create(['product_id' => $product->id]);

        $this->artisan('shop:import-astro-catalog', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run завершен. Изменения в БД не вносились.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('shop_categories', 0);
        $this->assertDatabaseCount('shop_products', 0);
        $this->assertDatabaseCount('shop_product_images', 0);
    }

    public function test_command_refuses_to_overwrite_non_empty_shop_catalog_without_truncate(): void
    {
        Product::factory()->create();

        $shopCategory = ShopCategory::factory()->create();
        $shopProduct = ShopProduct::factory()->create(['category_id' => $shopCategory->id]);
        ShopProductImage::factory()->create(['shop_product_id' => $shopProduct->id]);

        $this->artisan('shop:import-astro-catalog')
            ->expectsOutputToContain('Таблицы shop_* уже содержат данные.')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('shop_categories', 1);
        $this->assertDatabaseCount('shop_products', 1);
        $this->assertDatabaseCount('shop_product_images', 1);
    }

    public function test_command_truncates_existing_shop_catalog_and_reimports_when_requested(): void
    {
        $sourceCategory = ProductCategory::factory()->create(['slug' => 'rings']);
        $sourceProduct = Product::factory()->create([
            'category_id' => $sourceCategory->id,
            'name' => 'Исходный товар',
        ]);
        ProductImage::factory()->create(['product_id' => $sourceProduct->id, 'path' => 'products/source.jpg']);

        $staleCategory = ShopCategory::factory()->create(['slug' => 'stale']);
        $staleProduct = ShopProduct::factory()->create([
            'category_id' => $staleCategory->id,
            'name' => 'Старый товар',
        ]);
        ShopProductImage::factory()->create([
            'shop_product_id' => $staleProduct->id,
            'path' => 'shop/products/stale.jpg',
        ]);

        $this->artisan('shop:import-astro-catalog', ['--truncate' => true])
            ->expectsOutputToContain('Перед импортом каталог Мёд был очищен.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('shop_categories', 1);
        $this->assertDatabaseCount('shop_products', 1);
        $this->assertDatabaseCount('shop_product_images', 1);
        $this->assertDatabaseMissing('shop_categories', ['slug' => 'stale']);
        $this->assertDatabaseMissing('shop_products', ['name' => 'Старый товар']);
        $this->assertDatabaseHas('shop_products', ['name' => 'Исходный товар']);
        $this->assertDatabaseHas('shop_product_images', ['path' => 'products/source.jpg']);
    }

    public function test_command_refuses_truncate_when_shop_orders_exist(): void
    {
        Product::factory()->create();

        $shopCategory = ShopCategory::factory()->create();
        $shopProduct = ShopProduct::factory()->create(['category_id' => $shopCategory->id]);
        $shopOrder = ShopOrder::factory()->create();

        ShopOrderItem::factory()->create([
            'shop_order_id' => $shopOrder->id,
            'shop_product_id' => $shopProduct->id,
        ]);

        $this->artisan('shop:import-astro-catalog', ['--truncate' => true])
            ->expectsOutputToContain('Очистка shop-каталога запрещена:')
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseCount('shop_categories', 1);
        $this->assertDatabaseCount('shop_products', 1);
        $this->assertDatabaseCount('shop_order_items', 1);
    }
}
