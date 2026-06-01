<?php

namespace App\Http\Api\Requests;

class HandleYookassaWebhookRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'max:100'],
            'event' => ['required', 'string', 'in:payment.waiting_for_capture,payment.succeeded,payment.canceled'],
            'object' => ['required', 'array'],
            'object.id' => ['required', 'string', 'max:255'],
            'object.status' => ['required', 'string', 'in:pending,waiting_for_capture,succeeded,canceled'],
        ];
    }
}
