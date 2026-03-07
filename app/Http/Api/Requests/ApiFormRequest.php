<?php

namespace App\Http\Api\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Базовый FormRequest для API: перехватывает failedValidation и возвращает
 * стандартный envelope вместо дефолтного Laravel-ответа.
 */
abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'code'    => 'VALIDATION_ERROR',
                'data'    => null,
                'message' => 'Данные не прошли валидацию',
                'errors'  => $validator->errors()->toArray(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
