<?php

namespace App\Http\Requests\Homework;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('homework')) ?? false;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['sometimes', 'integer', 'exists:course_sessions,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
