<?php

namespace App\Http\Api\Controllers;

use App\Enums\OrderStatus;
use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\CreateShopOrderRequest;
use App\Http\Api\Requests\ListOrdersRequest;
use App\Http\Api\Resources\ShopOrderFullResource;
use App\Http\Api\Resources\ShopOrderShortResource;
use App\Models\ShopOrder;
use App\Models\User;
use App\Services\CreateShopOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ShopOrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CreateShopOrderService $createOrderService) {}

    public function index(ListOrdersRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $limit = (int) $request->input('limit', 20);
        $sort = $request->input('sort', '-created_at');

        $query = ShopOrder::query()
            ->where('user_id', $user->id)
            ->withCount('items');

        if ($request->filled('status')) {
            $query->where('status', OrderStatus::from($request->input('status')));
        }

        [$column, $direction] = str_starts_with($sort, '-')
            ? [ltrim($sort, '-'), 'desc']
            : [$sort, 'asc'];

        $paginator = $query
            ->orderBy($column, $direction)
            ->paginate($limit, ['*'], 'page', (int) $request->input('page', 1));

        return $this->success(
            data: ShopOrderShortResource::collection($paginator)->resolve(),
            meta: $this->paginationMeta($paginator),
        );
    }

    public function store(CreateShopOrderRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $order = $this->createOrderService->execute($user, $request->validated());

        return $this->success(
            data: new ShopOrderFullResource($order),
            message: 'Заказ магазина успешно создан',
            status: Response::HTTP_CREATED,
        );
    }

    public function show(int $id): JsonResponse
    {
        $order = ShopOrder::with(['items.product.images', 'items.product.category', 'deliveryAddress'])->find($id);

        if ($order === null) {
            return $this->error(
                code: 'NOT_FOUND',
                message: 'Shop order not found',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        /** @var User $user */
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            return $this->error(
                code: 'FORBIDDEN',
                message: 'Недостаточно прав для просмотра заказа магазина',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        return $this->success(data: new ShopOrderFullResource($order));
    }
}
