<?php

namespace App\Http\Requests\Exam;

use App\Models\ExamQuestion;
use Illuminate\Foundation\Http\FormRequest;

class StoreOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');
        $exam = $question instanceof ExamQuestion ? $question->exam : null;

        return $exam !== null && ($this->user()?->can('update', $exam) ?? false);
    }

    public function rules(): array
    {
        return [
            'option_text' => ['required', 'string', 'max:1000'],
            'is_correct' => ['boolean'],
        ];
    }
}
