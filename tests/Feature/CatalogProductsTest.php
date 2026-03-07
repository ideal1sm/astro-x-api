<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogProductsTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/catalog/products';

    // ── Базовая структура ─────────────────────────────────────────────────────

    public function test_returns_success_with_meta(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonStructure([
                'code', 'data', 'meta' => ['page', 'limit', 'total', 'pages'], 'message', 'errors',
            ])
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.page', 1);
    }

    public function test_response_data_structure(): void
    {
        $cat     = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id]);
        ProductImage::factory()->create(['product_id' => $product->id]);

        $response = $this->getJson($this->url)->assertOk();

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'short_description', 'price', 'color', 'composition', 'inlay', 'brand', 'category', 'images', 'created_at'],
            ],
        ]);

        // price всегда строка формата "XXXXX.XX"
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $response->json('data.0.price'));
    }

    public function test_empty_catalog_returns_zero_total(): void
    {
        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('data', []);
    }

    // ── Пагинация ─────────────────────────────────────────────────────────────

    public function test_pagination_limit_and_pages(): void
    {
        Product::factory()->count(5)->create();

        $this->getJson("{$this->url}?limit=2&page=1")
            ->assertOk()
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.pages', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_second_page_returns_correct_items(): void
    {
        Product::factory()->count(5)->create();

        $this->getJson("{$this->url}?limit=3&page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.page', 2);
    }

    // ── Фильтры цены ─────────────────────────────────────────────────────────

    public function test_filter_price_min(): void
    {
        Product::factory()->create(['price' => 1000]);
        Product::factory()->create(['price' => 5000]);
        Product::factory()->create(['price' => 10000]);

        $this->getJson("{$this->url}?price_min=4000")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filter_price_max(): void
    {
        Product::factory()->create(['price' => 1000]);
        Product::factory()->create(['price' => 5000]);
        Product::factory()->create(['price' => 10000]);

        $this->getJson("{$this->url}?price_max=5000")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filter_price_range(): void
    {
        Product::factory()->create(['price' => 1000]);
        Product::factory()->create(['price' => 5000]);
        Product::factory()->create(['price' => 10000]);

        $this->getJson("{$this->url}?price_min=2000&price_max=8000")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_price_max_less_than_price_min_returns_422(): void
    {
        $this->getJson("{$this->url}?price_min=5000&price_max=1000")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['price_max']]);
    }

    // ── Фильтр has_images ─────────────────────────────────────────────────────

    public function test_has_images_returns_only_products_with_images(): void
    {
        $withImages = Product::factory()->create();
        ProductImage::factory()->create(['product_id' => $withImages->id]);
        Product::factory()->create(); // без изображений

        $response = $this->getJson("{$this->url}?has_images=true")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->assertEquals($withImages->id, $response->json('data.0.id'));
    }

    public function test_has_images_false_returns_all_products(): void
    {
        Product::factory()->count(2)->create();

        $this->getJson("{$this->url}?has_images=false")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    // ── Фильтр по категории ───────────────────────────────────────────────────

    public function test_filter_by_category_slug(): void
    {
        $rings     = ProductCategory::factory()->create(['slug' => 'rings']);
        $necklaces = ProductCategory::factory()->create(['slug' => 'necklaces']);

        Product::factory()->count(2)->create(['category_id' => $rings->id]);
        Product::factory()->create(['category_id' => $necklaces->id]);

        $this->getJson("{$this->url}?category_slug=rings")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filter_by_category_id(): void
    {
        $cat1 = ProductCategory::factory()->create();
        $cat2 = ProductCategory::factory()->create();

        Product::factory()->create(['category_id' => $cat1->id]);
        Product::factory()->count(2)->create(['category_id' => $cat2->id]);

        $this->getJson("{$this->url}?category_id={$cat1->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_category_id_takes_precedence_over_category_slug(): void
    {
        $rings     = ProductCategory::factory()->create(['slug' => 'rings']);
        $necklaces = ProductCategory::factory()->create(['slug' => 'necklaces']);

        Product::factory()->create(['category_id' => $rings->id]);
        $necklaceProduct = Product::factory()->create(['category_id' => $necklaces->id]);

        // Передаём category_id нecklaces и slug rings — должны победить нecklaces
        $response = $this->getJson("{$this->url}?category_id={$necklaces->id}&category_slug=rings")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->assertEquals($necklaceProduct->id, $response->json('data.0.id'));
    }

    public function test_nonexistent_category_id_returns_422(): void
    {
        $this->getJson("{$this->url}?category_id=99999")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    // ── Сортировка ────────────────────────────────────────────────────────────

    public function test_sort_price_desc(): void
    {
        Product::factory()->create(['price' => 1000]);
        Product::factory()->create(['price' => 5000]);

        $response = $this->getJson("{$this->url}?sort=-price")->assertOk();

        $prices = collect($response->json('data'))->pluck('price')->values()->all();
        $this->assertEquals(['5000.00', '1000.00'], $prices);
    }

    public function test_sort_price_asc(): void
    {
        Product::factory()->create(['price' => 5000]);
        Product::factory()->create(['price' => 1000]);

        $response = $this->getJson("{$this->url}?sort=price")->assertOk();

        $prices = collect($response->json('data'))->pluck('price')->values()->all();
        $this->assertEquals(['1000.00', '5000.00'], $prices);
    }

    public function test_sort_name_asc(): void
    {
        Product::factory()->create(['name' => 'Zebra ring']);
        Product::factory()->create(['name' => 'Apple ring']);

        $response = $this->getJson("{$this->url}?sort=name")->assertOk();

        $this->assertEquals('Apple ring', $response->json('data.0.name'));
    }

    public function test_sort_name_desc(): void
    {
        Product::factory()->create(['name' => 'Apple ring']);
        Product::factory()->create(['name' => 'Zebra ring']);

        $response = $this->getJson("{$this->url}?sort=-name")->assertOk();

        $this->assertEquals('Zebra ring', $response->json('data.0.name'));
    }

    // ── Фильтр zodiac_signs ───────────────────────────────────────────────────

    public function test_zodiac_signs_single_filter(): void
    {
        Product::factory()->create(['zodiac_signs' => ['aries', 'leo']]);
        Product::factory()->create(['zodiac_signs' => ['scorpio']]);
        Product::factory()->create(['zodiac_signs' => []]);

        $this->getJson("{$this->url}?zodiac_signs[]=aries")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_zodiac_signs_or_filter(): void
    {
        Product::factory()->create(['zodiac_signs' => ['aries']]);
        Product::factory()->create(['zodiac_signs' => ['leo']]);
        Product::factory()->create(['zodiac_signs' => ['scorpio']]);

        $this->getJson("{$this->url}?zodiac_signs[]=aries&zodiac_signs[]=leo")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_zodiac_signs_no_match_returns_zero(): void
    {
        Product::factory()->create(['zodiac_signs' => ['aries']]);

        $this->getJson("{$this->url}?zodiac_signs[]=capricorn")
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_invalid_zodiac_sign_returns_422(): void
    {
        $this->getJson("{$this->url}?zodiac_signs[]=dragon")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    // ── Прочие фильтры ────────────────────────────────────────────────────────

    public function test_filter_by_brand(): void
    {
        Product::factory()->create(['brand' => 'SOKOLOV']);
        Product::factory()->create(['brand' => 'Pandora']);

        $this->getJson("{$this->url}?brand=SOKOLOV")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_composition_like_filter(): void
    {
        Product::factory()->create(['composition' => 'серебро 925']);
        Product::factory()->create(['composition' => 'золото 585']);

        $this->getJson("{$this->url}?composition=серебро")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    // ── Валидация ─────────────────────────────────────────────────────────────

    public function test_invalid_sort_returns_422(): void
    {
        $this->getJson("{$this->url}?sort=invalid_field")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['sort']]);
    }

    public function test_limit_above_max_returns_422(): void
    {
        $this->getJson("{$this->url}?limit=101")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_negative_price_min_returns_422(): void
    {
        $this->getJson("{$this->url}?price_min=-100")
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }
}
