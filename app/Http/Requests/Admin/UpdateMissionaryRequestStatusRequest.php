<?php

namespace App\Http\Requests\Admin;

use App\Enums\MissionaryRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMissionaryRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Admins are unrestricted — any status may transition to any other.
            'status' => ['required', 'string', Rule::in(MissionaryRequestStatus::values())],
        ];
    }
}
