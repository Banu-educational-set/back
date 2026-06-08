<?php

namespace App\Http\Requests\Course;

use App\Models\Course;
use App\Services\PrerequisiteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('course')) ?? false;
    }

    public function rules(): array
    {
        return [
            'term_id' => ['sometimes', 'integer', 'exists:terms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'capacity' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'prerequisite_course_ids' => ['sometimes', 'nullable', 'array'],
            'prerequisite_course_ids.*' => ['integer', 'distinct', 'exists:courses,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (! $this->has('prerequisite_course_ids')) {
            return;
        }

        $validator->after(function (Validator $v) {
            /** @var Course $course */
            $course = $this->route('course');
            $prereqIds = $this->prereqIds();

            if ($prereqIds === []) {
                return;
            }

            if (in_array($course->id, $prereqIds, true)) {
                $v->errors()->add('prerequisite_course_ids', 'A course cannot be its own prerequisite.');

                return;
            }

            $termId = $this->has('term_id')
                ? (int) $this->input('term_id')
                : (int) $course->term_id;

            $mismatched = Course::query()
                ->whereIn('id', $prereqIds)
                ->get(['id', 'term_id'])
                ->filter(fn (Course $c) => (int) $c->term_id !== $termId)
                ->pluck('id')
                ->all();

            if ($mismatched !== []) {
                $v->errors()->add('prerequisite_course_ids', 'Prerequisite courses must belong to the same term as this course.');
            }

            if (app(PrerequisiteService::class)->wouldCreateCourseCycle($course->id, $prereqIds)) {
                $v->errors()->add('prerequisite_course_ids', 'Assigning these prerequisites would create a cycle.');
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
