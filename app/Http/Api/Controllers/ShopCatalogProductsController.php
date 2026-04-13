<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\ShopCatalogProductsRequest;
use App\Http\Api\Resources\ShopProductShortResource;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;

class ShopCatalogProductsController
{
    use ApiResponse;

    private const SORT_MAP = [
        'price'      => 'price',
        'name'       => 'name',
        'created_at' => 'created_at',
    ];

    public function __invoke(ShopCatalogProductsRequest $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $query = ShopProduct::query()->with(['images', 'category']);

        if ($request->filled('category_id')) {
            $query->where('shop_products.category_id', $request->integer('category_id'));
        } elseif ($request->filled('category_slug')) {
            $query->whereHas(
                'category',
                fn ($q) => $q->where('slug', $request->string('category_slug')->toString()),
            );
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->string('brand')->toString());
        }

        if ($request->filled('color')) {
            $query->where('color', $request->string('color')->toString());
        }

        if ($request->filled('composition')) {
            $query->where('composition', 'like', '%' . $request->string('composition')->toString() . '%');
        }

        if ($request->filled('inlay')) {
            $query->where('inlay', 'like', '%' . $request->string('inlay')->toString() . '%');
        }

        if ($request->filled('lock_type')) {
            $query->where('lock_type', $request->string('lock_type')->toString());
        }

        if ($request->filled('production')) {
            $query->where('production', $request->string('production')->toString());
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        $signs = array_values(array_filter((array) $request->input('zodiac_signs', [])));
        if (! empty($signs)) {
            $query->where(function ($q) use ($signs): void {
                foreach ($signs as $sign) {
                    $q->orWhereJsonContains('zodiac_signs', $sign);
                }
            });
        }

        if ($request->boolean('has_images')) {
            $query->whereHas('images');
        }

        [$column, $direction] = $this->parseSort($request->input('sort', '-created_at'));
        $query->orderBy($column, $direction);

        $paginator = $query->paginate(perPage: $limit, page: $page);

        return $this->success(
            data: ShopProductShortResource::collection($paginator),
            meta: $this->paginationMeta($paginator),
        );
    }

    private function parseSort(string $sort): array
    {
        $desc = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');

        if (! array_key_exists($field, self::SORT_MAP)) {
            return ['created_at', 'desc'];
        }

        return [self::SORT_MAP[$field], $desc ? 'desc' : 'asc'];
    }
}
