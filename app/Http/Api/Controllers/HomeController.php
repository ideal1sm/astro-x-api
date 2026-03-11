<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Resources\HomeCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/home
     *
     * Возвращает категории для главной страницы, каждая с до 8 товарами.
     *
     * Производительность: 3 SQL-запроса независимо от числа категорий:
     *   1) SELECT product_categories WHERE ...
     *   2) SELECT products WHERE category_id IN (...)
     *   3) SELECT product_images WHERE product_id IN (...)
     */
    public function __invoke(): JsonResponse
    {
        $query = ProductCategory::query()
            ->where('show_on_home', true)
            ->orderBy('name');

        $categories = $query
            ->with([
                'products' => fn ($q) => $q->with('images')->latest(),
            ])
            ->get();

        return $this->success(HomeCategoryResource::collection($categories));
    }
}
