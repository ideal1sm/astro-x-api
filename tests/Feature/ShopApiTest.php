<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductAvailabilityStatus;
use App\Mail\ShopOrderCreatedManagerMail;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ShopCategory;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.yookassa.shop_id' => 'test-shop-id',
            'services.yookassa.secret_key' => 'test-secret-key',
            'services.yookassa.base_url' => 'https://api.yookassa.test/v3',
            'shop.notifications.email.enabled' => false,
            'shop.notifications.email.recipients' => [],
            'shop.notifications.telegram.enabled' => false,
            'shop.notifications.telegram.bot_token' => null,
            'shop.notifications.telegram.chat_ids' => [],
        ]);
    }

    public function test_shop_catalog_products_use_shop_products_only(): void
    {
        Product::factory()->create(['name' => 'Astro product']);

        $shopCategory = ShopCategory::factory()->inCatalog()->create(['name' => 'Мед', 'slug' => 'honey']);
        $shopProduct = ShopProduct::factory()->create([
            'category_id' => $shopCategory->id,
            'name' => 'Липовый мед',
        ]);
        ShopProductImage::factory()->create(['shop_product_id' => $shopProduct->id]);

        $response = $this->getJson('/api/v1/shop/catalog/products')->assertOk();

        $response
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $shopProduct->id)
            ->assertJsonPath('data.0.category.slug', 'honey')
            ->assertJsonPath('data.0.availability_status', 'in_stock')
            ->assertJsonPath('data.0.is_purchasable', true);
    }

    public function test_shop_catalog_categories_use_shop_categories_only(): void
    {
        ProductCategory::factory()->inCatalog()->create(['name' => 'Кольца']);
        $shopCategory = ShopCategory::factory()->inCatalog()->create(['name' => 'Мед']);

        $response = $this->getJson('/api/v1/shop/catalog/categories')->assertOk();

        $response
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $shopCategory->id)
            ->assertJsonPath('data.0.name', 'Мед');
    }

    public function test_user_can_create_shop_order_with_yookassa_payment(): void
    {
        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_order_1', '1300.00'), 200)]);

        $user = User::factory()->create();
        $shopProduct = ShopProduct::factory()->create([
            'price' => '500.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 5,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
                'delivery_method' => 'courier',
                'delivery_price' => 300,
                'delivery_payload' => [
                    'type' => 'courier',
                    'address' => 'Москва, ул. Пушкина, д. 10, кв. 5',
                ],
                'delivery_city' => 'Москва',
                'delivery_address' => 'Москва, ул. Пушкина, д. 10, кв. 5',
                'delivery_comment' => 'Позвонить за час',
                'items' => [
                    ['shop_product_id' => $shopProduct->id, 'quantity' => 2],
                ],
            ]));

        $response
            ->assertCreated()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('data.items_total', '1000.00')
            ->assertJsonPath('data.delivery_price', '300.00')
            ->assertJsonPath('data.total', '1300.00')
            ->assertJsonPath('data.payment_method', 'yookassa')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.payment_amount', '1300.00')
            ->assertJsonPath('data.payment_id', 'pay_order_1')
            ->assertJsonPath('data.payment_confirmation_url', 'https://pay.example.test/pay/pay_order_1')
            ->assertJsonPath('data.delivery_method', 'courier')
            ->assertJsonPath('data.delivery_city', 'Москва')
            ->assertJsonPath('data.delivery_address', 'Москва, ул. Пушкина, д. 10, кв. 5')
            ->assertJsonPath('data.items.0.shop_product_id', $shopProduct->id)
            ->assertJsonPath('data.customer_name', 'Иван Иванов')
            ->assertJsonPath('data.customer_phone', '+7 (999) 123-45-67')
            ->assertJsonPath('data.customer_email', 'ivan@example.com')
            ->assertJsonPath('data.personal_data_consent', true);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('shop_orders', 1);
        $this->assertDatabaseCount('shop_order_items', 1);
        $this->assertDatabaseHas('shop_orders', [
            'user_id' => $user->id,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 (999) 123-45-67',
            'customer_email' => 'ivan@example.com',
            'delivery_method' => 'courier',
            'delivery_city' => 'Москва',
            'delivery_address' => 'Москва, ул. Пушкина, д. 10, кв. 5',
            'payment_method' => 'yookassa',
            'payment_status' => 'pending',
            'yookassa_payment_id' => 'pay_order_1',
        ]);
        $this->assertDatabaseHas('shop_products', [
            'id' => $shopProduct->id,
            'stock_quantity' => 3,
        ]);

        Http::assertSentCount(1);
    }

    public function test_guest_can_create_shop_order_with_pickup_and_pending_payment(): void
    {
        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_guest_1', '1950.00'), 200)]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '750.00',
            'availability_status' => ProductAvailabilityStatus::Preorder,
            'stock_quantity' => null,
        ]);

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'customer_name' => 'Гостевой клиент',
            'customer_phone' => '+7 999 555 44 33',
            'customer_email' => 'guest@example.com',
            'delivery_method' => 'pickup_point',
            'delivery_price' => 450,
            'delivery_payload' => [
                'type' => 'pickup_point',
                'provider' => 'manual',
                'pickup_point' => 'ПВЗ на Ленина, 5',
            ],
            'recipient_name' => 'Пётр Петров',
            'recipient_phone' => '+7 999 555 44 34',
            'delivery_city' => 'Казань',
            'delivery_pickup_point' => 'ПВЗ на Ленина, 5',
            'delivery_comment' => 'После 18:00',
            'items' => [
                ['shop_product_id' => $shopProduct->id, 'quantity' => 2],
            ],
            'notes' => 'Оставить у консьержа',
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('data.items_total', '1500.00')
            ->assertJsonPath('data.delivery_price', '450.00')
            ->assertJsonPath('data.total', '1950.00')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.delivery_method', 'pickup_point')
            ->assertJsonPath('data.delivery_pickup_point', 'ПВЗ на Ленина, 5')
            ->assertJsonPath('data.customer_name', 'Гостевой клиент')
            ->assertJsonPath('data.customer_email', 'guest@example.com')
            ->assertJsonPath('data.personal_data_consent', true)
            ->assertJsonPath('data.notes', 'Оставить у консьержа');

        $this->assertDatabaseHas('shop_orders', [
            'user_id' => null,
            'customer_name' => 'Гостевой клиент',
            'customer_phone' => '+7 999 555 44 33',
            'customer_email' => 'guest@example.com',
            'delivery_method' => 'pickup_point',
            'delivery_city' => 'Казань',
            'delivery_pickup_point' => 'ПВЗ на Ленина, 5',
            'notes' => 'Оставить у консьержа',
            'payment_status' => 'pending',
        ]);

        $order = ShopOrder::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('450.00', (string) $order->delivery_price);
        $this->assertSame([
            'type' => 'pickup_point',
            'provider' => 'manual',
            'pickup_point' => 'ПВЗ на Ленина, 5',
        ], $order->delivery_payload);
        $this->assertNull($shopProduct->fresh()->stock_quantity);
        $this->assertSame(ProductAvailabilityStatus::Preorder, $shopProduct->fresh()->availability_status);
    }

    public function test_shop_order_creation_sends_email_notification_to_configured_managers(): void
    {
        Mail::fake();

        config([
            'shop.notifications.email.enabled' => true,
            'shop.notifications.email.recipients' => ['manager1@example.com', 'manager2@example.com'],
        ]);

        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_mail_1', '700.00'), 200)]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '500.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 3,
        ]);
        ShopProductImage::factory()->create([
            'shop_product_id' => $shopProduct->id,
            'path' => 'products/test-email-image.jpg',
        ]);

        $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
        ]))->assertCreated();

        Mail::assertSent(ShopOrderCreatedManagerMail::class, function (ShopOrderCreatedManagerMail $mail): bool {
            $html = $mail->render();

            return $mail->hasTo('manager1@example.com')
                && $mail->hasTo('manager2@example.com')
                && str_contains($html, 'Новый заказ Мёд #')
                && str_contains($html, 'Иван Иванов')
                && str_contains($html, 'ПВЗ на Ленина, 5')
                && str_contains($html, 'test-email-image.jpg')
                && str_contains($html, '/admin/shop-orders/');
        });
    }

    public function test_shop_order_creation_skips_email_when_recipients_are_not_configured(): void
    {
        Mail::fake();

        config([
            'shop.notifications.email.enabled' => true,
            'shop.notifications.email.recipients' => [],
        ]);

        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_mail_2', '700.00'), 200)]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '500.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 3,
        ]);

        $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
        ]))->assertCreated();

        Mail::assertNothingSent();
    }

    public function test_shop_order_email_renders_fallback_when_product_has_no_image(): void
    {
        Mail::fake();

        config([
            'shop.notifications.email.enabled' => true,
            'shop.notifications.email.recipients' => ['manager@example.com'],
        ]);

        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_mail_3', '700.00'), 200)]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '500.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 3,
        ]);

        $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
        ]))->assertCreated();

        Mail::assertSent(ShopOrderCreatedManagerMail::class, function (ShopOrderCreatedManagerMail $mail): bool {
            $html = $mail->render();

            return $mail->hasTo('manager@example.com')
                && str_contains($html, 'Изображение не загружено');
        });
    }

    public function test_shop_order_creation_sends_telegram_notification_to_configured_chats(): void
    {
        config([
            'shop.notifications.telegram.enabled' => true,
            'shop.notifications.telegram.bot_token' => 'telegram-test-token',
            'shop.notifications.telegram.chat_ids' => ['1001', '1002'],
        ]);

        Http::fake([
            $this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_tg_1', '700.00'), 200),
            $this->telegramSendMessageUrlPattern() => Http::response(['ok' => true, 'result' => ['message_id' => 1]], 200),
        ]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '500.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 3,
        ]);

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
        ]));

        $response->assertCreated();

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://api.telegram.org/bottelegram-test-token/sendMessage') {
                return false;
            }

            $payload = $request->data();

            return in_array($payload['chat_id'] ?? null, ['1001', '1002'], true)
                && ($payload['parse_mode'] ?? null) === 'HTML'
                && in_array((string) ($payload['disable_web_page_preview'] ?? ''), ['1', 'true'], true)
                && str_contains((string) ($payload['text'] ?? ''), 'Новый заказ Мёд #')
                && str_contains((string) ($payload['text'] ?? ''), 'Иван Иванов')
                && str_contains((string) ($payload['text'] ?? ''), 'Открыть заказ в админке');
        });
    }

    public function test_shop_order_creation_skips_telegram_when_chat_ids_are_not_configured(): void
    {
        config([
            'shop.notifications.telegram.enabled' => true,
            'shop.notifications.telegram.bot_token' => 'telegram-test-token',
            'shop.notifications.telegram.chat_ids' => [],
        ]);

        Http::fake([
            $this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_tg_2', '700.00'), 200),
            $this->telegramSendMessageUrlPattern() => Http::response(['ok' => true], 200),
        ]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '500.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 3,
        ]);

        $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
        ]))->assertCreated();

        Http::assertSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://api.yookassa.test/v3/payments');
        });

        Http::assertNotSent(function ($request): bool {
            return str_starts_with($request->url(), 'https://api.telegram.org/');
        });
    }

    public function test_telegram_api_error_does_not_break_shop_order_creation(): void
    {
        config([
            'shop.notifications.telegram.enabled' => true,
            'shop.notifications.telegram.bot_token' => 'telegram-test-token',
            'shop.notifications.telegram.chat_ids' => ['1001'],
        ]);

        Http::fake([
            $this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_tg_3', '700.00'), 200),
            $this->telegramSendMessageUrlPattern() => Http::response(['ok' => false], 500),
        ]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '500.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 3,
        ]);

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('code', 'SUCCESS');

        $this->assertDatabaseCount('shop_orders', 1);
    }

    public function test_guest_checkout_validates_required_fields_including_payment(): void
    {
        $shopProduct = ShopProduct::factory()->create();

        $response = $this->postJson('/api/v1/shop/orders', [
            'items' => [
                ['shop_product_id' => $shopProduct->id, 'quantity' => 1],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'customer_name',
                    'customer_phone',
                    'customer_email',
                    'personal_data_consent',
                    'payment_method',
                    'payment_return_url',
                    'delivery_method',
                    'delivery_price',
                    'delivery_payload',
                    'recipient_name',
                    'recipient_phone',
                    'delivery_city',
                ],
            ]);
    }

    public function test_guest_checkout_validates_phone_email_and_return_url_format(): void
    {
        $shopProduct = ShopProduct::factory()->create();

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'customer_name' => 'Покупатель',
            'customer_phone' => '123',
            'customer_email' => 'bad-email',
            'payment_return_url' => 'not-a-url',
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'customer_phone',
                    'customer_email',
                    'payment_return_url',
                ],
            ]);
    }

    public function test_guest_checkout_requires_address_or_pickup_point(): void
    {
        $shopProduct = ShopProduct::factory()->create();

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_method' => 'manual',
            'delivery_payload' => ['type' => 'manual'],
            'delivery_address' => null,
            'delivery_pickup_point' => null,
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'delivery_address',
                    'delivery_pickup_point',
                ],
            ]);
    }

    public function test_checkout_prohibits_legacy_delivery_address_id(): void
    {
        $user = User::factory()->create();
        $shopProduct = ShopProduct::factory()->create(['price' => '500.00']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
                'delivery_method' => 'courier',
                'delivery_price' => 300,
                'delivery_payload' => ['type' => 'courier'],
                'delivery_city' => 'Москва',
                'delivery_address' => 'Москва, ул. Пушкина, д. 10, кв. 5',
                'delivery_pickup_point' => null,
                'delivery_address_id' => 123,
            ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'delivery_address_id',
                ],
            ]);
    }

    public function test_guest_cannot_list_shop_orders(): void
    {
        $this->getJson('/api/v1/shop/orders')->assertStatus(401);
    }

    public function test_shop_product_card_returns_stock_fields(): void
    {
        $shopProduct = ShopProduct::factory()->create([
            'availability_status' => ProductAvailabilityStatus::OutOfStock,
            'stock_quantity' => 0,
        ]);

        $response = $this->getJson("/api/v1/shop/catalog/products/{$shopProduct->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.availability_status', 'out_of_stock')
            ->assertJsonPath('data.stock_quantity', 0)
            ->assertJsonPath('data.is_purchasable', false);
    }

    public function test_cannot_create_shop_order_for_out_of_stock_product(): void
    {
        $shopProduct = ShopProduct::factory()->create([
            'availability_status' => ProductAvailabilityStatus::OutOfStock,
            'stock_quantity' => 0,
        ]);

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct));

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'items.0.quantity',
                ],
            ]);
    }

    public function test_cannot_create_shop_order_if_requested_quantity_exceeds_stock(): void
    {
        $shopProduct = ShopProduct::factory()->create([
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 1,
        ]);

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'items' => [
                ['shop_product_id' => $shopProduct->id, 'quantity' => 2],
            ],
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'items.0.quantity',
                ],
            ]);
    }

    public function test_order_decrements_stock_and_marks_product_out_of_stock_when_depleted(): void
    {
        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->pendingPaymentResponse('pay_stock_1', '1400.00'), 200)]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '600.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 2,
        ]);

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
            'items' => [
                ['shop_product_id' => $shopProduct->id, 'quantity' => 2],
            ],
        ]));

        $response->assertCreated();

        $shopProduct->refresh();

        $this->assertSame(0, $shopProduct->stock_quantity);
        $this->assertSame(ProductAvailabilityStatus::OutOfStock, $shopProduct->availability_status);
    }

    public function test_payment_creation_failure_rolls_back_order_and_restores_stock(): void
    {
        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response(['type' => 'error'], 500)]);

        $shopProduct = ShopProduct::factory()->create([
            'price' => '600.00',
            'availability_status' => ProductAvailabilityStatus::InStock,
            'stock_quantity' => 2,
        ]);

        $response = $this->postJson('/api/v1/shop/orders', $this->makeOrderPayload($shopProduct, [
            'delivery_price' => 200,
            'items' => [
                ['shop_product_id' => $shopProduct->id, 'quantity' => 2],
            ],
        ]));

        $response
            ->assertStatus(502)
            ->assertJsonPath('code', 'PAYMENT_PROVIDER_ERROR');

        $this->assertDatabaseCount('shop_orders', 0);
        $this->assertDatabaseCount('shop_order_items', 0);
        $this->assertDatabaseHas('shop_products', [
            'id' => $shopProduct->id,
            'stock_quantity' => 2,
            'availability_status' => ProductAvailabilityStatus::InStock->value,
        ]);
    }

    public function test_yookassa_webhook_marks_order_paid_and_moves_it_in_progress(): void
    {
        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->succeededPaymentResponse('pay_success_1', '1300.00'), 200)]);

        $order = ShopOrder::factory()->create([
            'status' => OrderStatus::Created,
            'payment_method' => PaymentMethod::Yookassa,
            'payment_status' => PaymentStatus::Pending,
            'yookassa_payment_id' => 'pay_success_1',
            'payment_amount' => '1300.00',
            'total' => '1300.00',
        ]);

        $response = $this->postJson('/api/v1/shop/payments/yookassa/webhook', [
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'pay_success_1',
                'status' => 'succeeded',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');

        $this->assertDatabaseHas('shop_orders', [
            'id' => $order->id,
            'status' => OrderStatus::InProgress->value,
            'payment_status' => PaymentStatus::Succeeded->value,
            'yookassa_payment_id' => 'pay_success_1',
        ]);
    }

    public function test_yookassa_canceled_webhook_restores_reserved_stock_once(): void
    {
        Http::fake([$this->yookassaPaymentUrlPattern() => Http::response($this->canceledPaymentResponse('pay_cancel_1', '800.00'), 200)]);

        $shopProduct = ShopProduct::factory()->create([
            'availability_status' => ProductAvailabilityStatus::OutOfStock,
            'stock_quantity' => 0,
        ]);

        $order = ShopOrder::factory()->create([
            'status' => OrderStatus::Created,
            'payment_method' => PaymentMethod::Yookassa,
            'payment_status' => PaymentStatus::Pending,
            'yookassa_payment_id' => 'pay_cancel_1',
            'total' => '800.00',
            'payment_amount' => '800.00',
        ]);

        ShopOrderItem::factory()->create([
            'shop_order_id' => $order->id,
            'shop_product_id' => $shopProduct->id,
            'quantity' => 2,
            'price' => '300.00',
            'total' => '600.00',
        ]);

        $payload = [
            'event' => 'payment.canceled',
            'object' => [
                'id' => 'pay_cancel_1',
                'status' => 'canceled',
            ],
        ];

        $this->postJson('/api/v1/shop/payments/yookassa/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');
        $this->postJson('/api/v1/shop/payments/yookassa/webhook', $payload)
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');

        $this->assertDatabaseHas('shop_orders', [
            'id' => $order->id,
            'status' => OrderStatus::Canceled->value,
            'payment_status' => PaymentStatus::Canceled->value,
        ]);
        $this->assertDatabaseHas('shop_products', [
            'id' => $shopProduct->id,
            'stock_quantity' => 2,
            'availability_status' => ProductAvailabilityStatus::InStock->value,
        ]);
    }

    private function makeOrderPayload(ShopProduct $product, array $overrides = []): array
    {
        return array_replace_recursive([
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+7 (999) 123-45-67',
            'customer_email' => 'ivan@example.com',
            'personal_data_consent' => true,
            'payment_method' => PaymentMethod::Yookassa->value,
            'payment_return_url' => 'https://shop.example.test/orders/success',
            'delivery_method' => 'pickup_point',
            'delivery_price' => 200,
            'delivery_payload' => [
                'type' => 'pickup_point',
                'provider' => 'manual',
                'pickup_point' => 'ПВЗ на Ленина, 5',
            ],
            'recipient_name' => 'Иван Иванов',
            'recipient_phone' => '+7 (999) 123-45-67',
            'delivery_city' => 'Казань',
            'delivery_address' => null,
            'delivery_pickup_point' => 'ПВЗ на Ленина, 5',
            'delivery_comment' => 'После 18:00',
            'items' => [
                ['shop_product_id' => $product->id, 'quantity' => 1],
            ],
            'notes' => null,
        ], $overrides);
    }

    private function pendingPaymentResponse(string $paymentId, string $amount): array
    {
        return [
            'id' => $paymentId,
            'status' => 'pending',
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'confirmation_url' => "https://pay.example.test/pay/{$paymentId}",
            ],
            'metadata' => [
                'order_id' => '1',
            ],
        ];
    }

    private function succeededPaymentResponse(string $paymentId, string $amount): array
    {
        return [
            'id' => $paymentId,
            'status' => 'succeeded',
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB',
            ],
            'paid_at' => now()->toIso8601String(),
            'metadata' => [
                'order_id' => '1',
            ],
        ];
    }

    private function canceledPaymentResponse(string $paymentId, string $amount): array
    {
        return [
            'id' => $paymentId,
            'status' => 'canceled',
            'amount' => [
                'value' => $amount,
                'currency' => 'RUB',
            ],
            'cancellation_details' => [
                'reason' => 'canceled_by_merchant',
            ],
            'metadata' => [
                'order_id' => '1',
            ],
        ];
    }

    private function yookassaPaymentUrlPattern(): string
    {
        return 'https://api.yookassa.test/v3/payments*';
    }

    private function telegramSendMessageUrlPattern(): string
    {
        return 'https://api.telegram.org/bot*/sendMessage';
    }
}
