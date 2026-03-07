<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/catalog/categories';

    // ── Базовая структура ─────────────────────────────────────────────────────

    public function test_returns_success_with_meta(): void
    {
        ProductCategory::factory()->count(3)->create();

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
        ProductCategory::factory()->create();

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

    // ── Пагинация ─────────────────────────────────────────────────────────────

    public function test_pagination(): void
    {
        ProductCategory::factory()->count(5)->create();

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
        ProductCategory::factory()->create(['name' => 'Цепочки',  'slug' => 'chains']);
        ProductCategory::factory()->create(['name' => 'Браслеты', 'slug' => 'bracelets']);
        ProductCategory::factory()->create(['name' => 'Кольца',   'slug' => 'rings']);

        // Без параметра sort — дефолт 'name' ASC
        $response = $this->getJson($this->url)->assertOk();

        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertEquals(['Браслеты', 'Кольца', 'Цепочки'], $names);
    }

    public function test_sort_by_name_asc_explicit(): void
    {
        ProductCategory::factory()->create(['name' => 'Цепочки',  'slug' => 'chains']);
        ProductCategory::factory()->create(['name' => 'Браслеты', 'slug' => 'bracelets']);

        $response = $this->getJson("{$this->url}?sort=name")->assertOk();

        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertEquals(['Браслеты', 'Цепочки'], $names);
    }

    public function test_sort_by_name_desc(): void
    {
        ProductCategory::factory()->create(['name' => 'Браслеты', 'slug' => 'bracelets']);
        ProductCategory::factory()->create(['name' => 'Цепочки',  'slug' => 'chains']);

        $response = $this->getJson("{$this->url}?sort=-name")->assertOk();

        $this->assertEquals('Цепочки', $response->json('data.0.name'));
        $this->assertEquals('Браслеты', $response->json('data.1.name'));
    }

    // ── Фильтрация по show_in_catalog ─────────────────────────────────────────

    /**
     * Проверяет, что контроллер фильтрует категории по show_in_catalog=true,
     * когда эта колонка присутствует в БД.
     *
     * Колонка добавляется и удаляется внутри теста; RefreshDatabase оборачивает
     * каждый тест в транзакцию (SQLite поддерживает транзакционный DDL),
     * поэтому изменение схемы не «утечёт» в соседние тесты.
     */
    public function test_filters_by_show_in_catalog_when_column_exists(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->boolean('show_in_catalog')->default(true);
        });

        ProductCategory::factory()->create(['name' => 'visible', 'show_in_catalog' => true]);
        ProductCategory::factory()->create(['name' => 'hidden',  'show_in_catalog' => false]);

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'visible');
    }

    public function test_returns_all_categories_when_show_in_catalog_column_absent(): void
    {
        // Гарантируем, что колонки нет (стандартное состояние до миграции)
        $this->assertFalse(
            Schema::hasColumn('product_categories', 'show_in_catalog'),
            'Этот тест актуален только до применения миграции show_in_catalog.',
        );

        ProductCategory::factory()->count(3)->create();

        $this->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
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
