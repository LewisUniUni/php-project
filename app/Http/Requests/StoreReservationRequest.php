<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $header = $this->headers->get('Idempotency-Key');

        if (is_string($header) && ! $this->filled('idempotency_key')) {
            $this->merge(['idempotency_key' => $header]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'sku_code' => ['required', 'string', 'max:64'],
            'location_code' => ['required', 'string', 'max:64'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reference' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$/'],
            'warehouse_code' => ['required', 'string', 'max:32'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
