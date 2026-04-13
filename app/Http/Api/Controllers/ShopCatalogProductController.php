<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Resources\ShopProductFullResource;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ShopCatalogProductController
{
    use ApiResponse;

    public function __invoke(int $id): JsonResponse
    {
        $product = ShopProduct::with(['category', 'images'])->find($id);

        if ($product === null) {
            return $this->error(
                code: 'NOT_FOUND',
                message: 'Shop product not found',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        return $this->success(data: new ShopProductFullResource($product));
    }
}
