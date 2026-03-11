<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/catalog/categories';

    // ── Базовая структура ─────────────────────────────────────────────────────

    public function test_returns_success_with_meta(): void
    {
        ProductCategory::factory()->inCatalog()->count(3)->create();

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonStructure([
                'code', 'data', 'meta' => ['page', 'limit', 'total', 'pages'], 'message', 'errors',
            ])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_response_data_structure(): void
    {
        ProductCategory::factory()->inCatalog()->create();

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'slug', 'description', 'created_at', 'updated_at']],
            ]);
    }

    public function test_empty_returns_zero_total(): void
    {
        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('data', []);
    }

    // ── Фильтрация по show_in_catalog ─────────────────────────────────────────

    public function test_returns_only_show_in_catalog_categories(): void
    {
        ProductCategory::factory()->inCatalog()->create(['name' => 'visible']);
        ProductCategory::factory()->create(['name' => 'hidden', 'show_in_catalog' => false]);

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'visible');
    }

    public function test_hidden_categories_do_not_appear(): void
    {
        ProductCategory::factory()->count(3)->create(['show_in_catalog' => false]);

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    // ── Пагинация ─────────────────────────────────────────────────────────────

    public function test_pagination(): void
    {
        ProductCategory::factory()->inCatalog()->count(5)->create();

        $this->getJson("{$this->url}?limit=2&page=1")
            ->assertOk()
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.pages', 3)
            ->assertJsonCount(2, 'data');
    }

    // ── Сортировка ────────────────────────────────────────────────────────────

    public function test_sort_by_name_asc_is_default(): void
    {
        ProductCategory::factory()->inCatalog()->create(['name' => 'Цепочки',  'slug' => 'chains']);
        ProductCategory::factory()->inCatalog()->create(['name' => 'Браслеты', 'slug' => 'bracelets']);
        ProductCategory::factory()->inCatalog()->create(['name' => 'Кольца',   'slug' => 'rings']);

        $response = $this->getJson($this->url)->assertOk();

        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertEquals(['Браслеты', 'Кольца', 'Цепочки'], $names);
    }

    public function test_sort_by_name_asc_explicit(): void
    {
        ProductCategory::factory()->inCatalog()->create(['name' => 'Цепочки',  'slug' => 'chains']);
        ProductCategory::factory()->inCatalog()->create(['name' => 'Браслеты', 'slug' => 'bracelets']);

        $names = collect(
            $this->getJson("{$this->url}?sort=name")->assertOk()->json('data')
        )->pluck('name')->values()->all();

        $this->assertEquals(['Браслеты', 'Цепочки'], $names);
    }

    public function test_sort_by_name_desc(): void
    {
        ProductCategory::factory()->inCatalog()->create(['name' => 'Браслеты', 'slug' => 'bracelets']);
        ProductCategory::factory()->inCatalog()->create(['name' => 'Цепочки',  'slug' => 'chains']);

        $response = $this->getJson("{$this->url}?sort=-name")->assertOk();

        $this->assertEquals('Цепочки', $response->json('data.0.name'));
        $this->assertEquals('Браслеты', $response->json('data.1.name'));
    }

    // ── Валидация ─────────────────────────────────────────────────────────────

    public function test_invalid_sort_returns_422(): void
    {
        $this->getJson("{$this->url}?sort=price")
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
}
