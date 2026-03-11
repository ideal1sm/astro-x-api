<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Register ──────────────────────────────────────────────────────────────

    public function test_user_can_register(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Иван Иванов',
            'email'                 => 'ivan@example.com',
            'password'              => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])
            ->assertStatus(201)
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonStructure([
                'code', 'message', 'errors',
                'data' => ['token', 'user' => ['id', 'name', 'email', 'email_verified_at', 'created_at']],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'ivan@example.com']);
        Notification::assertSentTo(
            User::where('email', 'ivan@example.com')->first(),
            VerifyEmailNotification::class,
        );
    }

    public function test_register_fails_when_email_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Тест',
            'email'                 => 'taken@example.com',
            'password'              => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_register_fails_when_passwords_dont_match(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Тест',
            'email'                 => 'test@example.com',
            'password'              => 'secret12345',
            'password_confirmation' => 'different_password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => Hash::make('secret12345'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'secret12345',
        ])
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonStructure([
                'data' => ['token', 'user' => ['id', 'email']],
            ]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong_password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('data', null);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'secret12345',
        ])
            ->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('data', true);

        // Токен удалён из БД
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_auth(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401);
    }

    // ── Email confirm ─────────────────────────────────────────────────────────

    public function test_email_confirmation(): void
    {
        $user  = User::factory()->create(['email_verified_at' => null]);
        $token = Str::uuid()->toString();
        Cache::put("email_verify:{$token}", $user->id, now()->addHours(24));

        $this->postJson('/api/v1/auth/email/confirm', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull(Cache::get("email_verify:{$token}"));
    }

    public function test_email_confirmation_fails_with_invalid_token(): void
    {
        $this->postJson('/api/v1/auth/email/confirm', ['token' => 'invalid-token'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_TOKEN');
    }

    public function test_email_confirmation_is_idempotent(): void
    {
        $user  = User::factory()->create(['email_verified_at' => now()]);
        $token = Str::uuid()->toString();
        Cache::put("email_verify:{$token}", $user->id, now()->addHours(24));

        // Повторное подтверждение — всё равно SUCCESS
        $this->postJson('/api/v1/auth/email/confirm', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');
    }

    // ── Resend email ──────────────────────────────────────────────────────────

    public function test_resend_email_requires_auth(): void
    {
        $this->postJson('/api/v1/auth/email/resend')
            ->assertStatus(401);
    }

    public function test_resend_email_sends_notification(): void
    {
        Notification::fake();

        $user  = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/resend')
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_email_returns_error_if_already_verified(): void
    {
        $user  = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/resend')
            ->assertStatus(422)
            ->assertJsonPath('code', 'EMAIL_ALREADY_VERIFIED');
    }

    // ── Password reset flow ───────────────────────────────────────────────────

    public function test_password_forgot_always_returns_success(): void
    {
        // Не раскрываем, есть ли email в БД
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS');
    }

    public function test_password_reset_flow(): void
    {
        $user = User::factory()->create([
            'email'    => 'reset@example.com',
            'password' => Hash::make('old_password'),
        ]);

        // Генерируем токен через Password Broker
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'token'                 => $token,
            'email'                 => 'reset@example.com',
            'password'              => 'new_password123',
            'password_confirmation' => 'new_password123',
        ])
            ->assertOk()
            ->assertJsonPath('code', 'SUCCESS')
            ->assertJsonPath('data', true);

        // Новый пароль работает
        $this->assertTrue(Hash::check('new_password123', $user->fresh()->password));
        // Все токены отозваны
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/password/reset', [
            'token'                 => 'invalid-token',
            'email'                 => 'reset@example.com',
            'password'              => 'new_password123',
            'password_confirmation' => 'new_password123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_TOKEN');
    }
}
