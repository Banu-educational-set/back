<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Exam::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:course_sessions,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
