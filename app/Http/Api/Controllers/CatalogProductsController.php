<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\CatalogProductsRequest;
use App\Http\Api\Resources\ProductShortResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class CatalogProductsController
{
    use ApiResponse;

    /**
     * Поля, по которым разрешена сортировка.
     * Ключ — значение параметра sort (без минуса), значение — реальная DB-колонка.
     */
    private const SORT_MAP = [
        'price'      => 'price',
        'name'       => 'name',
        'created_at' => 'created_at',
    ];

    /** Сортировка по умолчанию если параметр sort не передан. */
    private const DEFAULT_SORT = '-created_at';

    public function __invoke(CatalogProductsRequest $request): JsonResponse
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $query = Product::query()->with(['images', 'category']);

        // ── Фильтрация ─────────────────────────────────────────────────────────

        // category_id имеет приоритет над category_slug, если переданы оба.
        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->integer('category_id'));
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

        // JSON-фильтр по zodiac_signs: хотя бы одно из переданных значений
        // должно присутствовать в JSON-массиве products.zodiac_signs.
        // whereJsonContains работает с MySQL, PostgreSQL и SQLite (Laravel 10+).
        $signs = array_values(array_filter((array) $request->input('zodiac_signs', [])));
        if (!empty($signs)) {
            $query->where(function ($q) use ($signs): void {
                foreach ($signs as $sign) {
                    $q->orWhereJsonContains('zodiac_signs', $sign);
                }
            });
        }

        if ($request->boolean('has_images')) {
            $query->whereHas('images');
        }

        // ── Сортировка ─────────────────────────────────────────────────────────
        [$column, $direction] = $this->parseSort($request->input('sort'));
        $query->orderBy($column, $direction);

        // ── Пагинация ──────────────────────────────────────────────────────────
        $paginator = $query->paginate(perPage: $limit, page: $page);

        return $this->success(
            data: ProductShortResource::collection($paginator),
            meta: $this->paginationMeta($paginator),
        );
    }

    /**
     * Разбирает строку сортировки вида "-price" или "name".
     *
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    private function parseSort(?string $sort): array
    {
        $sort ??= self::DEFAULT_SORT;

        $desc  = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');

        if (!array_key_exists($field, self::SORT_MAP)) {
            // Невалидное поле (не должно пройти валидацию, но fallback для надёжности)
            return ['created_at', 'desc'];
        }

        return [self::SORT_MAP[$field], $desc ? 'desc' : 'asc'];
    }
}
