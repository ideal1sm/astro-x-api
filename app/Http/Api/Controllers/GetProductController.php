<?php

namespace App\Http\Api\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetProductController
{
    public function __invoke(int $id): JsonResponse
    {
        $product = Product::with('images')->find($id);

        if (!$product) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'data' => null,
                'message' => 'Товар не найден',
                'errors' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'code' => 'SUCCESS',
            'data' => $product,
            'message' => '',
            'errors' => [],
        ]);
    }
}
