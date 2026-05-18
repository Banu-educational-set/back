<?php

namespace App\Http\Requests\Missionary;

use App\Enums\MissionaryRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(MissionaryRequestStatus::values())],
            'description' => ['nullable', 'string'],
        ];
    }
}
