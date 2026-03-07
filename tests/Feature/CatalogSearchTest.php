<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/catalog/search';

    // ── Валидация ─────────────────────────────────────────────────────────────

    public function test_missing_q_returns_422(): void
    {
        $this->getJson($this->url)
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['q']]);
    }

    public function test_q_too_short_returns_422(): void
    {
        $this->getJson("{$this->url}?q=a")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['q']]);
    }

    public function test_q_max_length_returns_422(): void
    {
        $this->getJson("{$this->url}?q=" . str_repeat('a', 201))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_invalid_type_returns_422(): void
    {
        $this->getJson("{$this->url}?q=ring&type=unknown")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['type']]);
    }

    // ── Базовая структура ответа ──────────────────────────────────────────────

    public function test_returns_success_envelope(): void
    {
        $this->getJson("{$this->url}?q=test")
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonStructure([
                'code', 'data' => ['products', 'categories'],
                'meta' => ['page', 'limit', 'total', 'pages'],
                'message', 'errors',
            ]);
    }

    public function test_no_results_returns_empty_lists(): void
    {
        $this->getJson("{$this->url}?q=нетакогословавбд")
            ->assertOk()
            ->assertJsonPath('data.products', [])
            ->assertJsonPath('data.categories', [])
            ->assertJsonPath('meta.total', 0);
    }

    // ── type=products ─────────────────────────────────────────────────────────

    public function test_type_products_returns_only_products(): void
    {
        Product::factory()->create(['name' => 'золотое кольцо']);
        ProductCategory::factory()->create(['name' => 'кольца', 'slug' => 'rings']);

        $response = $this->getJson("{$this->url}?q=кольцо&type=products")->assertOk();

        $response->assertJsonPath('data.categories', []);
        $this->assertNotEmpty($response->json('data.products'));
    }

    public function test_type_products_meta_reflects_products_total(): void
    {
        Product::factory()->create(['name' => 'кольцо А']);
        Product::factory()->create(['name' => 'кольцо Б']);
        ProductCategory::factory()->create(['name' => 'кольца', 'slug' => 'rings']);

        $response = $this->getJson("{$this->url}?q=кольцо&type=products")->assertOk();

        $this->assertEquals(2, $response->json('meta.total'));
    }

    // ── type=categories ───────────────────────────────────────────────────────

    public function test_type_categories_returns_only_categories(): void
    {
        Product::factory()->create(['name' => 'браслет серебро']);
        // Имя в нижнем регистре: SQLite LIKE чувствителен к регистру для кириллицы.
        // В MySQL с utf8mb4_unicode_ci совпадение произошло бы и для 'Браслеты'.
        ProductCategory::factory()->create(['name' => 'браслеты', 'slug' => 'bracelets']);

        $response = $this->getJson("{$this->url}?q=браслет&type=categories")->assertOk();

        $response->assertJsonPath('data.products', []);
        $this->assertNotEmpty($response->json('data.categories'));
    }

    public function test_type_categories_meta_reflects_categories_total(): void
    {
        ProductCategory::factory()->create(['name' => 'Кольца',   'slug' => 'rings']);
        ProductCategory::factory()->create(['name' => 'Кольцевые', 'slug' => 'ring-like']);

        $response = $this->getJson("{$this->url}?q=Кольц&type=categories")->assertOk();

        $this->assertEquals(2, $response->json('meta.total'));
    }

    // ── type=all (default) ────────────────────────────────────────────────────

    public function test_type_all_returns_both(): void
    {
        Product::factory()->create(['name' => 'серебряная цепочка']);
        ProductCategory::factory()->create(['name' => 'цепочки', 'slug' => 'chains']); // lowercase для SQLite

        $response = $this->getJson("{$this->url}?q=цепочк")->assertOk();

        $this->assertNotEmpty($response->json('data.products'));
        $this->assertNotEmpty($response->json('data.categories'));
    }

    public function test_type_all_meta_total_is_sum(): void
    {
        Product::factory()->create(['name'  => 'кольцо золото']);
        Product::factory()->create(['name'  => 'кольцо серебро']);
        ProductCategory::factory()->create(['name' => 'кольца', 'slug' => 'rings']); // lowercase для SQLite

        $response = $this->getJson("{$this->url}?q=кольц")->assertOk();

        // 2 products + 1 category = 3
        $this->assertEquals(3, $response->json('meta.total'));
    }

    // ── Поиск по полям ────────────────────────────────────────────────────────

    public function test_finds_product_by_name(): void
    {
        Product::factory()->create(['name' => 'уникальное украшение xyz']);
        Product::factory()->create(['name' => 'другой товар']);

        $response = $this->getJson("{$this->url}?q=xyz&type=products")->assertOk();

        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertStringContainsString('xyz', $response->json('data.products.0.name'));
    }

    public function test_finds_product_by_brand(): void
    {
        Product::factory()->create(['brand' => 'UNIQUEBRAND']);
        Product::factory()->create(['brand' => 'other']);

        $response = $this->getJson("{$this->url}?q=UNIQUEBRAND&type=products")->assertOk();

        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_finds_product_by_composition(): void
    {
        Product::factory()->create(['composition' => 'серебро 925 пробы']);
        Product::factory()->create(['composition' => 'золото 585']);

        $response = $this->getJson("{$this->url}?q=925&type=products")->assertOk();

        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_finds_category_by_name(): void
    {
        ProductCategory::factory()->create(['name' => 'Уникальная категория', 'slug' => 'unique-cat']);
        ProductCategory::factory()->create(['name' => 'Другая',               'slug' => 'other-cat']);

        $response = $this->getJson("{$this->url}?q=Уникальная&type=categories")->assertOk();

        $this->assertEquals(1, $response->json('meta.total'));
    }

    // ── Нормализация q ────────────────────────────────────────────────────────

    public function test_q_is_trimmed(): void
    {
        Product::factory()->create(['name' => 'кольцо']);

        $this->getJson("{$this->url}?q=+кольцо+&type=products")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_q_internal_spaces_collapsed(): void
    {
        // Два пробела между словами — ищем одним пробелом, должно найтись
        Product::factory()->create(['name' => 'золотое кольцо']);

        // Браузер кодирует пробел в +, несколько пробелов → схлопываются
        $this->getJson("{$this->url}?q=золотое+кольцо&type=products")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    // ── Структура data.products ────────────────────────────────────────────────

    public function test_product_resource_shape(): void
    {
        $cat = ProductCategory::factory()->create();
        $p   = Product::factory()->create(['name' => 'тест форма', 'category_id' => $cat->id]);
        ProductImage::factory()->create(['product_id' => $p->id]);

        $response = $this->getJson("{$this->url}?q=форма&type=products")->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'products' => [
                    '*' => ['id', 'name', 'price', 'category', 'images', 'created_at'],
                ],
            ],
        ]);
    }

    public function test_category_resource_shape(): void
    {
        ProductCategory::factory()->create(['name' => 'тест категория', 'slug' => 'test-cat']);

        $response = $this->getJson("{$this->url}?q=категория&type=categories")->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'categories' => [
                    '*' => ['id', 'name', 'slug'],
                ],
            ],
        ]);
    }
}
