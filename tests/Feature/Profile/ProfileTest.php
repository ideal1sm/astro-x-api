<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // ── GET /me ───────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create(['name' => 'Иван Иванов']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Иван Иванов')
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'email_verified_at', 'created_at']]);
    }

    public function test_guest_cannot_get_profile(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    // ── PATCH /me ─────────────────────────────────────────────────────────────

    public function test_user_can_update_name(): void
    {
        $user = User::factory()->create(['name' => 'Старое Имя']);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/me', ['name' => 'Новое Имя']);

        $response->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('message', 'Профиль обновлён')
            ->assertJsonPath('data.name', 'Новое Имя');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Новое Имя']);
    }

    public function test_update_profile_fails_if_name_is_empty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/me', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_update_profile_fails_if_name_too_long(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/me', ['name' => str_repeat('А', 256)])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->patchJson('/api/v1/me', ['name' => 'Хакер'])
            ->assertUnauthorized();
    }
}
