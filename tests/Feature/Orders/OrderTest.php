<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    // ── Create order ──────────────────────────────────────────────────────────

    public function test_user_can_create_order(): void
    {
        $user     = User::factory()->create();
        $product1 = Product::factory()->create(['price' => '1000.00']);
        $product2 = Product::factory()->create(['price' => '2500.00']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['product_id' => $product1->id, 'quantity' => 2],
                    ['product_id' => $product2->id, 'quantity' => 1],
                ],
                'notes' => 'Позвоните перед доставкой',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('message', 'Заказ успешно создан')
            ->assertJsonStructure([
                'data' => ['id', 'status', 'total', 'items', 'notes', 'created_at', 'updated_at'],
            ]);

        // status = created
        $response->assertJsonPath('data.status', 'created');

        // total = 1000*2 + 2500*1 = 4500
        $response->assertJsonPath('data.total', '4500.00');

        // Позиции сохранены в БД
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status'  => 'created',
        ]);
    }

    public function test_guest_cannot_create_order(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(401);
    }

    public function test_create_order_validates_items_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['items']]);
    }

    public function test_create_order_validates_product_exists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items' => [['product_id' => 99999, 'quantity' => 1]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_user_cannot_use_foreign_delivery_address(): void
    {
        $user    = User::factory()->create();
        $other   = User::factory()->create();
        $address = UserAddress::factory()->create(['user_id' => $other->id]);
        $product = Product::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items'               => [['product_id' => $product->id, 'quantity' => 1]],
                'delivery_address_id' => $address->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['delivery_address_id']]);
    }

    public function test_user_can_use_own_delivery_address(): void
    {
        $user    = User::factory()->create();
        $address = UserAddress::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => '500.00']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'items'               => [['product_id' => $product->id, 'quantity' => 1]],
                'delivery_address_id' => $address->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('code', 'SUCCESS');

        $this->assertDatabaseHas('orders', ['delivery_address_id' => $address->id]);
    }

    // ── List orders ───────────────────────────────────────────────────────────

    public function test_user_can_list_only_own_orders(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Order::factory()->count(3)->create(['user_id' => $user->id]);
        Order::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_guest_cannot_list_orders(): void
    {
        $this->getJson('/api/v1/orders')->assertStatus(401);
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(2)->create(['user_id' => $user->id, 'status' => OrderStatus::Created]);
        Order::factory()->count(1)->create(['user_id' => $user->id, 'status' => OrderStatus::Shipped]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?status=shipped');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'shipped');
    }

    public function test_list_orders_pagination(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?limit=2&page=1');

        $response->assertOk()
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.pages', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_orders_response_structure(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'status', 'total', 'items_count', 'created_at']],
                'meta' => ['page', 'limit', 'total', 'pages'],
            ]);
    }

    public function test_list_orders_invalid_status_returns_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders?status=paid')
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    // ── Show order ────────────────────────────────────────────────────────────

    public function test_user_can_view_own_order(): void
    {
        $user    = User::factory()->create();
        $product = Product::factory()->create(['price' => '3000.00']);
        $order   = Order::factory()->create(['user_id' => $user->id, 'total' => '3000.00']);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'price'      => '3000.00',
            'total'      => '3000.00',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonStructure([
                'data' => [
                    'id', 'status', 'total', 'notes',
                    'items' => ['*' => ['id', 'product_id', 'product', 'quantity', 'price', 'total']],
                    'delivery_address', 'created_at', 'updated_at',
                ],
            ]);
    }

    public function test_user_cannot_view_foreign_order(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_order_not_found_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/orders/99999')
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND')
            ->assertJsonPath('data', null);
    }

    public function test_guest_cannot_view_order(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/v1/orders/{$order->id}")->assertStatus(401);
    }
}
