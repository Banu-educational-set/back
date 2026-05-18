<?php

namespace App\Http\Requests\Homework;

use App\Models\Homework;
use Illuminate\Foundation\Http\FormRequest;

class StoreHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Homework::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:course_sessions,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
