<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\HandleYookassaWebhookRequest;
use App\Services\Payments\ShopOrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class YookassaWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ShopOrderPaymentService $paymentService) {}

    public function __invoke(HandleYookassaWebhookRequest $request): JsonResponse
    {
        $this->paymentService->handleYookassaWebhook($request->validated());

        return $this->success(data: null, message: 'Webhook ЮKassa обработан');
    }
}
