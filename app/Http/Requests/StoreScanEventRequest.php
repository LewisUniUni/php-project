<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreScanEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'uuid'],
            'device_id' => ['required', 'string', 'max:64'],
            'symbology' => ['required', 'string', 'max:32'],
            'payload' => ['required', 'string', 'max:512'],
            'warehouse_code' => ['nullable', 'string', 'max:32'],
            'recorded_at' => ['required', 'date'],
            'device' => ['nullable', 'array'],
            'device.model' => ['nullable', 'string', 'max:64'],
            'device.firmware' => ['nullable', 'string', 'max:32'],
        ];
    }
}
