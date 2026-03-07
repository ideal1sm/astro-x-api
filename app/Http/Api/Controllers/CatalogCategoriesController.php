<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\CatalogCategoriesRequest;
use App\Http\Api\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class CatalogCategoriesController
{
    use ApiResponse;

    private const SORT_MAP = [
        'name'       => 'name',
        'created_at' => 'created_at',
    ];

    /** Сортировка по умолчанию — name ASC (спека: "default": "name"). */
    private const DEFAULT_SORT = 'name';

    public function __invoke(CatalogCategoriesRequest $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $query = ProductCategory::query();

        // TODO: убрать проверку Schema::hasColumn после применения миграции.
        //       Нужно добавить: $table->boolean('show_in_catalog')->default(true);
        //       в таблицу product_categories (см. OpenAPI spec, поле [proposed]).
        //       После этого фильтр будет применяться всегда.
        if (Schema::hasColumn('product_categories', 'show_in_catalog')) {
            $query->where('show_in_catalog', true);
        }

        [$column, $direction] = $this->parseSort($request->input('sort'));
        $query->orderBy($column, $direction);

        $paginator = $query->paginate(perPage: $limit, page: $page);

        return $this->success(
            data: ProductCategoryResource::collection($paginator),
            meta: $this->paginationMeta($paginator),
        );
    }

    /** @return array{0: string, 1: 'asc'|'desc'} */
    private function parseSort(?string $sort): array
    {
        $sort ??= self::DEFAULT_SORT;
        $desc  = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');

        if (!array_key_exists($field, self::SORT_MAP)) {
            return ['name', 'asc'];
        }

        return [self::SORT_MAP[$field], $desc ? 'desc' : 'asc'];
    }
}
