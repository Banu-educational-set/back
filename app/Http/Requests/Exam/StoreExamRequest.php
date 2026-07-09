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
            'score' => ['sometimes', 'integer', 'min:1'],
            'minimum_score' => ['sometimes', 'integer', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_random' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            // Defaults match the DB column defaults: exams score out of 100.
            $score = $this->has('score') ? $this->integer('score') : 100;
            $min = $this->has('minimum_score') ? $this->integer('minimum_score') : 50;
            if ($score > 0 && $min > $score) {
                $v->errors()->add('minimum_score', __('errors.minimum_score_gt_score'));
            }
        });
    }
}
