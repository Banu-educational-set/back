<?php

namespace App\Http\Requests\Session;

use App\Enums\SessionType;
use App\Models\CourseSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CourseSession::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(SessionType::values())],
            'starts_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:500'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }
}
