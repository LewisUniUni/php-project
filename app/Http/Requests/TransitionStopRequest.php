<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\Delivery\Enums\StopStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransitionStopRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::enum(StopStatus::class)],
        ];
    }
}
