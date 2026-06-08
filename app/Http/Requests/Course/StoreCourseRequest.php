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
            'prerequisite_course_ids' => ['nullable', 'array'],
            'prerequisite_course_ids.*' => ['integer', 'distinct', 'exists:courses,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->filled('teacher_id')) {
                $teacher = \App\Models\User::find($this->integer('teacher_id'));
                if (! $teacher || ! $teacher->hasAnyRole([RoleName::Teacher->value, RoleName::Admin->value])) {
                    $v->errors()->add('teacher_id', 'teacher_id must reference a user with the teacher role.');
                }
            }

            $prereqIds = $this->prereqIds();
            if ($prereqIds === []) {
                return;
            }

            $termId = (int) $this->input('term_id');
            $mismatched = Course::query()
                ->whereIn('id', $prereqIds)
                ->get(['id', 'term_id'])
                ->filter(fn (Course $c) => (int) $c->term_id !== $termId)
                ->pluck('id')
                ->all();

            if ($mismatched !== []) {
                $v->errors()->add('prerequisite_course_ids', 'Prerequisite courses must belong to the same term as this course.');
            }
        });
    }

    /**
     * @return array<int>
     */
    private function prereqIds(): array
    {
        $ids = $this->input('prerequisite_course_ids', []);
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => is_numeric($v) ? (int) $v : null,
            $ids,
        ))));
    }
}
