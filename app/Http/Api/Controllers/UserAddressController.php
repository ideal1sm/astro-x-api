<?php

namespace App\Http\Api\Controllers;

use App\Http\Api\Concerns\ApiResponse;
use App\Http\Api\Requests\CreateUserAddressRequest;
use App\Http\Api\Requests\UpdateUserAddressRequest;
use App\Http\Api\Resources\UserAddressResource;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UserAddressController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/me/addresses
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        return $this->success(
            data: UserAddressResource::collection($addresses)->resolve(),
        );
    }

    /**
     * POST /api/v1/me/addresses
     */
    public function store(CreateUserAddressRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validated();

        $address = DB::transaction(function () use ($user, $data) {
            $isFirst = $user->addresses()->doesntExist();

            // Первый адрес → всегда дефолтный
            if ($isFirst) {
                $data['is_default'] = true;
            }

            // Если явно задан is_default=true → сбросить у остальных
            if (!empty($data['is_default'])) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create($data);
        });

        return $this->success(
            data: new UserAddressResource($address),
            message: 'Адрес добавлен',
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * PATCH /api/v1/me/addresses/{id}
     */
    public function update(UpdateUserAddressRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user    = Auth::user();
        $address = UserAddress::find($id);

        if ($address === null) {
            return $this->error(
                code: 'NOT_FOUND',
                message: 'Адрес не найден',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        if ($address->user_id !== $user->id) {
            return $this->error(
                code: 'FORBIDDEN',
                message: 'Нет доступа к этому адресу',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        $data = $request->validated();

        $address = DB::transaction(function () use ($user, $address, $data) {
            // Если устанавливаем is_default=true → сбросить у остальных
            if (!empty($data['is_default'])) {
                $user->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($data);

            return $address->fresh();
        });

        return $this->success(data: new UserAddressResource($address));
    }

    /**
     * DELETE /api/v1/me/addresses/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var User $user */
        $user    = Auth::user();
        $address = UserAddress::find($id);

        if ($address === null) {
            return $this->error(
                code: 'NOT_FOUND',
                message: 'Адрес не найден',
                status: Response::HTTP_NOT_FOUND,
            );
        }

        if ($address->user_id !== $user->id) {
            return $this->error(
                code: 'FORBIDDEN',
                message: 'Нет доступа к этому адресу',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        DB::transaction(function () use ($user, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            // Если удалён дефолтный адрес → назначить следующий по дате создания
            if ($wasDefault) {
                $next = $user->addresses()->orderBy('created_at')->first();
                $next?->update(['is_default' => true]);
            }
        });

        return $this->success(data: null, message: 'Адрес удалён');
    }
}
