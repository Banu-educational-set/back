<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'score' => ['required', 'integer', 'min:1'],
            'minimum_score' => ['required', 'integer', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_random' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $score = $this->integer('score');
            $min = $this->integer('minimum_score');
            if ($score > 0 && $min > $score) {
                $v->errors()->add('minimum_score', 'minimum_score cannot exceed score.');
            }
        });
    }
}
