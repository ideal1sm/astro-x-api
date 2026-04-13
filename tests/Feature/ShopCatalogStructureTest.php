<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShopCategory;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCatalogStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_product_belongs_to_shop_category_and_has_images(): void
    {
        $category = ShopCategory::factory()->inCatalog()->create(['name' => 'Мед']);
        $product = ShopProduct::factory()->create([
            'category_id' => $category->id,
            'name'        => 'Липовый мед',
            'price'       => '890.00',
        ]);

        ShopProductImage::factory()->count(2)->create(['shop_product_id' => $product->id]);

        $product->load(['category', 'images']);

        $this->assertTrue($product->category->is($category));
        $this->assertCount(2, $product->images);
        $this->assertSame('890.00', (string) $product->price);
    }

    public function test_shop_catalog_is_separate_from_astro_catalog(): void
    {
        $astroCategory = ProductCategory::factory()->create(['name' => 'Кольца']);
        $astroProduct = Product::factory()->create(['category_id' => $astroCategory->id]);

        $shopCategory = ShopCategory::factory()->create(['name' => 'Мед']);
        $shopProduct = ShopProduct::factory()->create(['category_id' => $shopCategory->id]);

        $this->assertDatabaseHas('products', ['id' => $astroProduct->id]);
        $this->assertDatabaseHas('product_categories', ['id' => $astroCategory->id]);
        $this->assertDatabaseHas('shop_products', ['id' => $shopProduct->id]);
        $this->assertDatabaseHas('shop_categories', ['id' => $shopCategory->id]);

        $this->assertSame(1, Product::count());
        $this->assertSame(1, ProductCategory::count());
        $this->assertSame(1, ShopProduct::count());
        $this->assertSame(1, ShopCategory::count());
    }

    public function test_shop_order_items_are_separate_from_astro_products(): void
    {
        $shopProduct = ShopProduct::factory()->create();
        $shopOrder = ShopOrder::factory()->create(['total' => '1200.00']);

        ShopOrderItem::factory()->create([
            'shop_order_id'   => $shopOrder->id,
            'shop_product_id' => $shopProduct->id,
            'quantity'        => 2,
            'price'           => '600.00',
            'total'           => '1200.00',
        ]);

        $shopOrder->load('items.product');

        $this->assertCount(1, $shopOrder->items);
        $this->assertTrue($shopOrder->items->first()->product->is($shopProduct));
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('shop_order_items', 1);
    }
}
