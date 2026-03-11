<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/home';

    // ── Базовая структура ─────────────────────────────────────────────────────

    public function test_home_returns_success(): void
    {
        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonStructure(['code', 'data', 'message', 'errors'])
            ->assertJsonIsArray('data');
    }

    public function test_home_returns_empty_when_no_categories(): void
    {
        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    // ── Категории и товары ────────────────────────────────────────────────────

    public function test_home_returns_categories_with_products(): void
    {
        $category = ProductCategory::factory()->onHome()->create(['name' => 'Кольца', 'slug' => 'rings']);
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson($this->url)->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => ['*' => ['id', 'name', 'slug', 'image', 'products']]])
            ->assertJsonPath('data.0.slug', 'rings')
            ->assertJsonCount(3, 'data.0.products');
    }

    public function test_home_products_have_correct_structure(): void
    {
        $category = ProductCategory::factory()->onHome()->create();
        $product  = Product::factory()->create(['category_id' => $category->id]);
        ProductImage::factory()->create(['product_id' => $product->id]);

        $this->getJson($this->url)->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => [
                    'products' => ['*' => ['id', 'name', 'price', 'images']],
                ]],
            ]);
    }

    public function test_home_category_with_no_products_returns_empty_products(): void
    {
        ProductCategory::factory()->onHome()->create(['name' => 'Пустая', 'slug' => 'empty']);

        $this->getJson($this->url)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.products', []);
    }

    public function test_home_limits_products_to_8_per_category(): void
    {
        $category = ProductCategory::factory()->onHome()->create();
        Product::factory()->count(12)->create(['category_id' => $category->id]);

        $response = $this->getJson($this->url)->assertOk();

        $this->assertCount(8, $response->json('data.0.products'));
    }

    public function test_home_image_is_null_when_no_image_on_category(): void
    {
        ProductCategory::factory()->onHome()->create();

        $this->getJson($this->url)->assertOk()
            ->assertJsonPath('data.0.image', null);
    }

    // ── Фильтрация по show_on_home ────────────────────────────────────────────

    public function test_home_respects_show_on_home(): void
    {
        ProductCategory::factory()->onHome()->create(['name' => 'visible']);
        ProductCategory::factory()->create(['name' => 'hidden', 'show_on_home' => false]);

        $response = $this->getJson($this->url)->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'visible');
    }

    public function test_home_categories_sorted_by_name_asc(): void
    {
        ProductCategory::factory()->onHome()->create(['name' => 'Цепочки', 'slug' => 'chains']);
        ProductCategory::factory()->onHome()->create(['name' => 'Браслеты', 'slug' => 'bracelets']);
        ProductCategory::factory()->onHome()->create(['name' => 'Кольца',   'slug' => 'rings']);

        $response = $this->getJson($this->url)->assertOk();

        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertEquals(['Браслеты', 'Кольца', 'Цепочки'], $names);
    }

    // ── Нет meta ──────────────────────────────────────────────────────────────

    public function test_home_has_no_meta_field(): void
    {
        $this->assertArrayNotHasKey('meta', $this->getJson($this->url)->assertOk()->json());
    }
}
