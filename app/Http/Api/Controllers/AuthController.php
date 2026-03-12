<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\Auth\ConfirmEmailRequest;
use App\Http\Api\Requests\Auth\ForgotPasswordRequest;
use App\Http\Api\Requests\Auth\LoginRequest;
use App\Http\Api\Requests\Auth\RegisterRequest;
use App\Http\Api\Requests\Auth\ResetPasswordRequest;
use App\Http\Api\Resources\UserResource;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/register
     *
     * Создаёт пользователя, отправляет email-верификацию, возвращает токен.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        $this->sendVerificationEmail($user);

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        return $this->success(
            data: ['token' => $token, 'user' => new UserResource($user)],
            message: 'Регистрация прошла успешно. Проверьте почту для подтверждения.',
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return $this->error(
                code: 'INVALID_CREDENTIALS',
                message: 'Неверный email или пароль',
                status: Response::HTTP_UNAUTHORIZED,
            );
        }

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        return $this->success(
            data: ['token' => $token, 'user' => new UserResource($user)],
        );
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Требует auth:sanctum.
     */
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return $this->success(data: true);
    }

    /**
     * POST /api/v1/auth/email/confirm
     *
     * Принимает токен, выставляет email_verified_at.
     */
    public function confirmEmail(ConfirmEmailRequest $request): JsonResponse
    {
        $token  = $request->input('token');
        $userId = Cache::get("email_verify:{$token}");

        if (! $userId) {
            return $this->error(
                code: 'INVALID_TOKEN',
                message: 'Токен недействителен или истёк',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = User::find($userId);

        if (! $user) {
            return $this->error(
                code: 'INVALID_TOKEN',
                message: 'Токен недействителен или истёк',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Cache::forget("email_verify:{$token}");

        return $this->success(data: new UserResource($user));
    }

    /**
     * POST /api/v1/auth/email/resend
     *
     * Требует auth:sanctum.
     */
    public function resendEmail(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return $this->error(
                code: 'EMAIL_ALREADY_VERIFIED',
                message: 'Email уже подтверждён',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $this->sendVerificationEmail($user);

        return $this->success(data: true, message: 'Письмо отправлено повторно');
    }

    /**
     * POST /api/v1/auth/password/forgot
     *
     * Использует стандартный Laravel Password Broker.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => $request->input('email')]);

        // Всегда возвращаем SUCCESS, чтобы не раскрывать, существует ли email.
        return $this->success(data: true, message: 'Если аккаунт существует — письмо отправлено');
    }

    /**
     * POST /api/v1/auth/password/reset
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                // Отзываем все токены после смены пароля
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(
                code: 'INVALID_TOKEN',
                message: 'Токен недействителен, истёк или email не найден',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->success(data: true, message: 'Пароль успешно изменён');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sendVerificationEmail(User $user): void
    {
        $token = Str::uuid()->toString();
        Cache::put("email_verify:{$token}", $user->id, now()->addHours(24));
        $user->notify(new VerifyEmailNotification($token));
    }
}
