<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_catalog_products_use_shop_products_only(): void
    {
        Product::factory()->create(['name' => 'Astro product']);

        $shopCategory = ShopCategory::factory()->inCatalog()->create(['name' => 'Мед', 'slug' => 'honey']);
        $shopProduct = ShopProduct::factory()->create([
            'category_id' => $shopCategory->id,
            'name'        => 'Липовый мед',
        ]);
        ShopProductImage::factory()->create(['shop_product_id' => $shopProduct->id]);

        $response = $this->getJson('/api/v1/shop/catalog/products')->assertOk();

        $response
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $shopProduct->id)
            ->assertJsonPath('data.0.category.slug', 'honey');
    }

    public function test_shop_catalog_categories_use_shop_categories_only(): void
    {
        ProductCategory::factory()->inCatalog()->create(['name' => 'Кольца']);
        $shopCategory = ShopCategory::factory()->inCatalog()->create(['name' => 'Мед']);

        $response = $this->getJson('/api/v1/shop/catalog/categories')->assertOk();

        $response
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $shopCategory->id)
            ->assertJsonPath('data.0.name', 'Мед');
    }

    public function test_user_can_create_shop_order_with_shop_product(): void
    {
        $user = User::factory()->create();
        $shopProduct = ShopProduct::factory()->create(['price' => '500.00']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shop/orders', [
                'items' => [
                    ['shop_product_id' => $shopProduct->id, 'quantity' => 2],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('data.total', '1000.00')
            ->assertJsonPath('data.items.0.shop_product_id', $shopProduct->id);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('shop_orders', 1);
        $this->assertDatabaseCount('shop_order_items', 1);
    }
}
