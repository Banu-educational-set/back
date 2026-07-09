<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            if (! $this->has('score') && ! $this->has('minimum_score')) {
                return;
            }
            /** @var \App\Models\Exam|null $exam */
            $exam = $this->route('exam');
            $score = $this->has('score') ? $this->integer('score') : (int) ($exam?->score ?? 0);
            $min = $this->has('minimum_score') ? $this->integer('minimum_score') : (int) ($exam?->minimum_score ?? 0);
            if ($score > 0 && $min > $score) {
                $v->errors()->add('minimum_score', __('errors.minimum_score_gt_score'));
            }
        });
    }
}
