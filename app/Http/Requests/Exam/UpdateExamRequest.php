<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('exam')) ?? false;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['sometimes', 'integer', 'exists:course_sessions,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
