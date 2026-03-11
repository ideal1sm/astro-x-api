<?php

namespace App\Http\Api\Controllers;

use App\Enums\OrderStatus;
use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\CreateOrderRequest;
use App\Http\Api\Requests\ListOrdersRequest;
use App\Http\Api\Resources\OrderFullResource;
use App\Http\Api\Resources\OrderShortResource;
use App\Models\Order;
use App\Models\User;
use App\Services\CreateOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CreateOrderService $createOrderService) {}

    /**
     * GET /api/v1/orders
     *
     * Список заказов текущего пользователя с пагинацией.
     */
    public function index(ListOrdersRequest $request): JsonResponse
    {
        /** @var User $user */
        $user  = Auth::user();
        $limit = (int) $request->input('limit', 20);
        $sort  = $request->input('sort', '-created_at');

        $query = Order::query()
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
            data: OrderShortResource::collection($paginator)->resolve(),
            meta: $this->paginationMeta($paginator),
        );
    }

    /**
     * POST /api/v1/orders
     *
     * Создание заказа.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        /** @var User $user */
        $user  = Auth::user();
        $order = $this->createOrderService->execute($user, $request->validated());

        return $this->success(
            data: new OrderFullResource($order),
            message: 'Заказ успешно создан',
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * GET /api/v1/orders/{id}
     *
     * Детальный заказ. Пользователь видит только собственные заказы.
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with(['items.product', 'deliveryAddress'])->find($id);

        if ($order === null) {
            return $this->error(
                code: 'NOT_FOUND',
                message: 'Order not found',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        /** @var User $user */
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            return $this->error(
                code: 'FORBIDDEN',
                message: 'Недостаточно прав для просмотра заказа',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        return $this->success(data: new OrderFullResource($order));
    }
}
