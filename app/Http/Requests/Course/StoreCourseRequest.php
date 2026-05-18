<?php

namespace App\Http\Requests\Course;

use App\Enums\RoleName;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Course::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->filled('teacher_id')) {
                return;
            }
            $teacher = \App\Models\User::find($this->integer('teacher_id'));
            if (! $teacher || ! $teacher->hasAnyRole([RoleName::Teacher->value, RoleName::Admin->value])) {
                $v->errors()->add('teacher_id', 'teacher_id must reference a user with the teacher role.');
            }
        });
    }
}
