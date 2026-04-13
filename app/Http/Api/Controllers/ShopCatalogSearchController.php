<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\ShopCatalogSearchRequest;
use App\Http\Api\Resources\ShopCategoryShortResource;
use App\Http\Api\Resources\ShopProductShortResource;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ShopCatalogSearchController
{
    use ApiResponse;

    public function __invoke(ShopCatalogSearchRequest $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $type = $request->input('type', 'all');

        $q = preg_replace('/\s+/', ' ', trim($request->string('q')->toString()));
        $pattern = '%' . $q . '%';

        $productsPaginator = null;
        $categoriesPaginator = null;

        if ($type === 'products' || $type === 'all') {
            $productsPaginator = ShopProduct::query()
                ->where(function ($query) use ($pattern): void {
                    $query->where('name', 'like', $pattern)
                        ->orWhere('description', 'like', $pattern)
                        ->orWhere('short_description', 'like', $pattern)
                        ->orWhere('brand', 'like', $pattern)
                        ->orWhere('inlay', 'like', $pattern)
                        ->orWhere('composition', 'like', $pattern)
                        ->orWhere('production', 'like', $pattern);
                })
                ->with(['images', 'category'])
                ->paginate(perPage: $limit, page: $page);
        }

        if ($type === 'categories' || $type === 'all') {
            $categoriesPaginator = ShopCategory::query()
                ->where(function ($query) use ($pattern): void {
                    $query->where('name', 'like', $pattern)
                        ->orWhere('description', 'like', $pattern);
                })
                ->paginate(perPage: $limit, page: $page);
        }

        return $this->success(
            data: [
                'products'   => $productsPaginator
                    ? ShopProductShortResource::collection($productsPaginator)
                    : [],
                'categories' => $categoriesPaginator
                    ? ShopCategoryShortResource::collection($categoriesPaginator)
                    : [],
            ],
            meta: $this->buildMeta($type, $page, $limit, $productsPaginator, $categoriesPaginator),
        );
    }

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

        $total = ($products?->total() ?? 0) + ($categories?->total() ?? 0);

        return [
            'page'  => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $total > 0 ? (int) ceil($total / $limit) : 1,
        ];
    }
}
