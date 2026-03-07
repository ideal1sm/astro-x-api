<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogProductTest extends TestCase
{
    use RefreshDatabase;

    // ── Success ───────────────────────────────────────────────────────────────

    public function test_get_product_success(): void
    {
        $category = ProductCategory::factory()->create();
        $product  = Product::factory()->create([
            'category_id'       => $category->id,
            'name'              => 'Кольцо с фианитом',
            'price'             => 4990.00,
            'brand'             => 'SOKOLOV',
            'color'             => 'серебряный',
            'composition'       => 'серебро 925',
            'inlay'             => 'фианит',
            'zodiac_signs'      => ['libra', 'scorpio'],
        ]);
        ProductImage::factory()->count(2)->create(['product_id' => $product->id]);

        $response = $this->getJson("/api/v1/catalog/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');

        $response->assertJsonStructure([
            'code',
            'data' => [
                'id', 'name', 'short_description', 'price', 'color',
                'composition', 'inlay', 'brand', 'description',
                'lock_type', 'length', 'production', 'zodiac_signs',
                'created_at', 'updated_at',
                'category' => ['id', 'name', 'slug'],
                'images'   => [['id', 'url']],
            ],
            'message',
            'errors',
        ]);

        // meta не должен присутствовать в ответе детальной карточки
        $this->assertArrayNotHasKey('meta', $response->json());
    }

    public function test_get_product_returns_correct_data(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Кольца', 'slug' => 'rings']);
        $product  = Product::factory()->create([
            'category_id'  => $category->id,
            'name'         => 'Золотое кольцо',
            'price'        => 12500.50,
            'zodiac_signs' => ['aries', 'leo'],
        ]);
        ProductImage::factory()->create(['product_id' => $product->id]);

        $response = $this->getJson("/api/v1/catalog/products/{$product->id}")->assertOk();

        $this->assertEquals($product->id,       $response->json('data.id'));
        $this->assertEquals('Золотое кольцо',    $response->json('data.name'));
        $this->assertEquals('12500.50',          $response->json('data.price'));
        $this->assertEquals(['aries', 'leo'],    $response->json('data.zodiac_signs'));
        $this->assertEquals($category->id,       $response->json('data.category.id'));
        $this->assertEquals('rings',             $response->json('data.category.slug'));
        $this->assertCount(1, $response->json('data.images'));
    }

    public function test_get_product_images_contain_url(): void
    {
        $product = Product::factory()->create();
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/test-image.jpg',
        ]);

        $response = $this->getJson("/api/v1/catalog/products/{$product->id}")->assertOk();

        $imageUrl = $response->json('data.images.0.url');
        $this->assertNotNull($imageUrl);
        $this->assertStringContainsString('test-image.jpg', $imageUrl);
    }

    public function test_get_product_without_category(): void
    {
        $product = Product::factory()->create(['category_id' => null]);

        $response = $this->getJson("/api/v1/catalog/products/{$product->id}")->assertOk();

        $this->assertNull($response->json('data.category'));
    }

    public function test_get_product_without_images_returns_empty_array(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/catalog/products/{$product->id}")->assertOk();

        $this->assertSame([], $response->json('data.images'));
    }

    public function test_get_product_zodiac_signs_defaults_to_empty_array(): void
    {
        $product = Product::factory()->create(['zodiac_signs' => null]);

        $response = $this->getJson("/api/v1/catalog/products/{$product->id}")->assertOk();

        $this->assertSame([], $response->json('data.zodiac_signs'));
    }

    // ── Not found ─────────────────────────────────────────────────────────────

    public function test_get_product_not_found(): void
    {
        $this->getJson('/api/v1/catalog/products/999999')
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND')
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['code', 'data', 'message', 'errors']);
    }
}
