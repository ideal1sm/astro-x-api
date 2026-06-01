<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;

class YookassaClient
{
    public function __construct(private readonly HttpFactory $http) {}

    public function createPayment(array $payload): array
    {
        return $this->request('post', '/payments', $payload);
    }

    public function getPayment(string $paymentId): array
    {
        return $this->request('get', "/payments/{$paymentId}");
    }

    private function request(string $method, string $uri, array $payload = []): array
    {
        $shopId = (string) config('services.yookassa.shop_id');
        $secretKey = (string) config('services.yookassa.secret_key');

        if ($shopId === '' || $secretKey === '') {
            throw new PaymentGatewayException('ЮKassa не настроена.');
        }

        $request = $this->http
            ->baseUrl((string) config('services.yookassa.base_url', 'https://api.yookassa.ru/v3'))
            ->withBasicAuth($shopId, $secretKey)
            ->acceptJson()
            ->asJson()
            ->withHeader('Idempotence-Key', (string) Str::uuid());

        try {
            $response = $request->{$method}($uri, $payload)->throw();
        } catch (RequestException $exception) {
            throw new PaymentGatewayException(
                message: 'Ошибка при обращении к ЮKassa.',
                previous: $exception,
            );
        }

        /** @var array $decoded */
        $decoded = $response->json();

        return $decoded;
    }
}
