<?php

namespace App\Http\Api\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function success(
        mixed $data,
        string $message = '',
        ?array $meta = null,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $payload = [
            'code'    => 'SUCCESS',
            'data'    => $data,
            'message' => $message,
            'errors'  => [],
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function error(
        string $code,
        string $message,
        array|object $errors = [],
        int $status = Response::HTTP_BAD_REQUEST,
    ): JsonResponse {
        return response()->json([
            'code'    => $code,
            'data'    => null,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Построить массив meta из Laravel LengthAwarePaginator.
     * Используется в success() как третий аргумент.
     */
    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'page'  => $paginator->currentPage(),
            'limit' => $paginator->perPage(),
            'total' => $paginator->total(),
            'pages' => $paginator->lastPage(),
        ];
    }
}
