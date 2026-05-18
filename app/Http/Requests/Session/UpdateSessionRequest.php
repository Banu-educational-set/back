<?php

namespace App\Http\Requests\Session;

use App\Enums\SessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('session')) ?? false;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in(SessionType::values())],
            'starts_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:500'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
        ];
    }
}
