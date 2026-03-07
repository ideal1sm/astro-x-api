<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Resources\ProductFullResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CatalogProductController
{
    use ApiResponse;

    public function __invoke(int $id): JsonResponse
    {
        $product = Product::with(['category', 'images'])->find($id);

        if ($product === null) {
            return $this->error(
                code: 'NOT_FOUND',
                message: 'Product not found',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        return $this->success(data: new ProductFullResource($product));
    }
}
