<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\UpdateProfileRequest;
use App\Http\Api\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/me
     */
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return $this->success(data: new UserResource($user));
    }

    /**
     * PATCH /api/v1/me
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->update($request->validated());

        return $this->success(
            data: new UserResource($user->fresh()),
            message: 'Профиль обновлён',
        );
    }
}
