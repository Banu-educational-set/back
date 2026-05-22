<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('course')) ?? false;
    }

    public function rules(): array
    {
        return [
            'term_id' => ['sometimes', 'nullable', 'integer', 'exists:terms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }
}
