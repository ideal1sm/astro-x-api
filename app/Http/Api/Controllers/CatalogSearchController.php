<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\CatalogSearchRequest;
use App\Http\Api\Resources\ProductCategoryShortResource;
use App\Http\Api\Resources\ProductShortResource;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogSearchController
{
    use ApiResponse;

    public function __invoke(CatalogSearchRequest $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $type  = $request->input('type', 'all');

        // Нормализация: убираем пробелы по краям и схлопываем внутренние
        $q       = preg_replace('/\s+/', ' ', trim($request->string('q')->toString()));
        $pattern = '%' . $q . '%';

        $productsPaginator    = null;
        $categoriesPaginator  = null;

        if ($type === 'products' || $type === 'all') {
            $productsPaginator = Product::query()
                ->where(function ($query) use ($pattern): void {
                    $query->where('name',              'like', $pattern)
                          ->orWhere('description',     'like', $pattern)
                          ->orWhere('short_description','like', $pattern)
                          ->orWhere('brand',           'like', $pattern)
                          ->orWhere('inlay',           'like', $pattern)
                          ->orWhere('composition',     'like', $pattern);
                })
                ->with(['images', 'category'])
                ->paginate(perPage: $limit, page: $page);
        }

        if ($type === 'categories' || $type === 'all') {
            $categoriesPaginator = ProductCategory::query()
                ->where(function ($query) use ($pattern): void {
                    $query->where('name',        'like', $pattern)
                          ->orWhere('description','like', $pattern);
                })
                ->paginate(perPage: $limit, page: $page);
        }

        return $this->success(
            data: [
                'products'   => $productsPaginator
                    ? ProductShortResource::collection($productsPaginator)
                    : [],
                'categories' => $categoriesPaginator
                    ? ProductCategoryShortResource::collection($categoriesPaginator)
                    : [],
            ],
            meta: $this->buildMeta($type, $page, $limit, $productsPaginator, $categoriesPaginator),
        );
    }

    /**
     * Строит массив meta в зависимости от типа поиска.
     *
     * type=products   → meta от paginator товаров
     * type=categories → meta от paginator категорий
     * type=all        → total = sum(оба), pages = ceil(total/limit).
     *                   Оба списка независимо пагинируются тем же page/limit —
     *                   это означает, что при переключении страниц оба списка
     *                   сдвигаются синхронно. Компромисс для MVP: клиент
     *                   получает единый курсор пагинации для обоих типов.
     */
    private function buildMeta(
        string $type,
        int $page,
        int $limit,
        ?LengthAwarePaginator $products,
        ?LengthAwarePaginator $categories,
    ): array {
        if ($type === 'products') {
            return $this->paginationMeta($products);
        }

        if ($type === 'categories') {
            return $this->paginationMeta($categories);
        }

        // type=all: суммируем total, pages — потолок от суммы
        $total = ($products?->total() ?? 0) + ($categories?->total() ?? 0);
        $pages = $total > 0 ? (int) ceil($total / $limit) : 1;

        return [
            'page'  => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $pages,
        ];
    }
}
