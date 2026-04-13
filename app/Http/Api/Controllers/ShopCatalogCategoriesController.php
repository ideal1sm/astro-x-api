<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\ShopCatalogCategoriesRequest;
use App\Http\Api\Resources\ShopCategoryResource;
use App\Models\ShopCategory;
use Illuminate\Http\JsonResponse;

class ShopCatalogCategoriesController
{
    use ApiResponse;

    private const SORT_MAP = [
        'name'       => 'name',
        'created_at' => 'created_at',
    ];

    public function __invoke(ShopCatalogCategoriesRequest $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $query = ShopCategory::query()->where('show_in_catalog', true);

        [$column, $direction] = $this->parseSort($request->input('sort', 'name'));
        $query->orderBy($column, $direction);

        $paginator = $query->paginate(perPage: $limit, page: $page);

        return $this->success(
            data: ShopCategoryResource::collection($paginator),
            meta: $this->paginationMeta($paginator),
        );
    }

    private function parseSort(string $sort): array
    {
        $desc = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');

        if (! array_key_exists($field, self::SORT_MAP)) {
            return ['name', 'asc'];
        }

        return [self::SORT_MAP[$field], $desc ? 'desc' : 'asc'];
    }
}
