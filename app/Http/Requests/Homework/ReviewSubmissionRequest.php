<?php

namespace App\Http\Requests\Homework;

use App\Enums\HomeworkSubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('submission')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    HomeworkSubmissionStatus::Accepted->value,
                    HomeworkSubmissionStatus::Denied->value,
                ]),
            ],
            'teacher_feedback' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
