<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAddressTest extends TestCase
{
    use RefreshDatabase;

    // ── GET /me/addresses ─────────────────────────────────────────────────────

    public function test_user_can_list_own_addresses(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        UserAddress::factory()->count(2)->create(['user_id' => $user->id]);
        UserAddress::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/addresses');

        $response->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonCount(2, 'data');
    }

    public function test_guest_cannot_list_addresses(): void
    {
        $this->getJson('/api/v1/me/addresses')
            ->assertUnauthorized();
    }

    // ── POST /me/addresses ────────────────────────────────────────────────────

    public function test_user_can_create_address(): void
    {
        $user = User::factory()->create();

        $payload = [
            'title'       => 'Домашний',
            'country'     => 'Россия',
            'city'        => 'Москва',
            'street'      => 'ул. Арбат, 10',
            'apartment'   => '5',
            'postal_code' => '119002',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/addresses', $payload);

        $response->assertCreated()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('message', 'Адрес добавлен')
            ->assertJsonPath('data.city', 'Москва')
            ->assertJsonStructure(['data' => [
                'id', 'user_id', 'title', 'country', 'city', 'street',
                'apartment', 'postal_code', 'is_default', 'created_at', 'updated_at',
            ]]);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'city'    => 'Москва',
        ]);
    }

    public function test_first_address_is_automatically_set_as_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/addresses', [
                'country'     => 'Россия',
                'city'        => 'Москва',
                'street'      => 'ул. Тверская, 1',
                'postal_code' => '125009',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_default', true);
    }

    public function test_creating_address_with_is_default_true_clears_other_defaults(): void
    {
        $user = User::factory()->create();

        $existing = UserAddress::factory()->create([
            'user_id'    => $user->id,
            'is_default' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/addresses', [
                'country'     => 'Россия',
                'city'        => 'Санкт-Петербург',
                'street'      => 'Невский пр., 1',
                'postal_code' => '190000',
                'is_default'  => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('user_addresses', [
            'id'         => $existing->id,
            'is_default' => false,
        ]);
    }

    public function test_create_address_requires_required_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/addresses', [])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_guest_cannot_create_address(): void
    {
        $this->postJson('/api/v1/me/addresses', [
            'country'     => 'Россия',
            'city'        => 'Москва',
            'street'      => 'ул. Арбат, 10',
            'postal_code' => '119002',
        ])->assertUnauthorized();
    }

    // ── PATCH /me/addresses/{id} ──────────────────────────────────────────────

    public function test_user_can_update_own_address(): void
    {
        $user    = User::factory()->create();
        $address = UserAddress::factory()->create(['user_id' => $user->id, 'city' => 'Москва']);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/me/addresses/{$address->id}", ['city' => 'Питер']);

        $response->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('data.city', 'Питер');

        $this->assertDatabaseHas('user_addresses', ['id' => $address->id, 'city' => 'Питер']);
    }

    public function test_setting_address_as_default_clears_other_defaults(): void
    {
        $user = User::factory()->create();

        $default    = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $nonDefault = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/me/addresses/{$nonDefault->id}", ['is_default' => true])
            ->assertOk()
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('user_addresses', ['id' => $default->id, 'is_default' => false]);
        $this->assertDatabaseHas('user_addresses', ['id' => $nonDefault->id, 'is_default' => true]);
    }

    public function test_user_cannot_update_foreign_address(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $address = UserAddress::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/me/addresses/{$address->id}", ['city' => 'Хак'])
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_update_address_returns_404_for_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/me/addresses/99999', ['city' => 'Москва'])
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    // ── DELETE /me/addresses/{id} ─────────────────────────────────────────────

    public function test_user_can_delete_own_address(): void
    {
        $user    = User::factory()->create();
        $address = UserAddress::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/addresses/{$address->id}")
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('message', 'Адрес удалён');

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_deleting_default_address_assigns_default_to_next(): void
    {
        $user = User::factory()->create();

        $default = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $next    = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/addresses/{$default->id}")
            ->assertOk();

        $this->assertDatabaseMissing('user_addresses', ['id' => $default->id]);
        $this->assertDatabaseHas('user_addresses', ['id' => $next->id, 'is_default' => true]);
    }

    public function test_deleting_non_default_address_does_not_change_defaults(): void
    {
        $user = User::factory()->create();

        $default    = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $nonDefault = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/addresses/{$nonDefault->id}")
            ->assertOk();

        $this->assertDatabaseHas('user_addresses', ['id' => $default->id, 'is_default' => true]);
    }

    public function test_user_cannot_delete_foreign_address(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $address = UserAddress::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/addresses/{$address->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_delete_address_returns_404_for_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/me/addresses/99999')
            ->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_guest_cannot_delete_address(): void
    {
        $address = UserAddress::factory()->create();

        $this->deleteJson("/api/v1/me/addresses/{$address->id}")
            ->assertUnauthorized();
    }
}
