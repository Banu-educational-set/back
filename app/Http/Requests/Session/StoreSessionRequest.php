<?php

namespace App\Http\Requests\Session;

use App\Enums\SessionType;
use App\Models\CourseSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CourseSession::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(SessionType::values())],
            'starts_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:500'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'prerequisite_session_ids' => ['nullable', 'array'],
            'prerequisite_session_ids.*' => ['integer', 'distinct', 'exists:course_sessions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $prereqIds = $this->prereqIds();
            if ($prereqIds === [] || ! $this->filled('course_id')) {
                return;
            }

            $courseId = (int) $this->input('course_id');
            $mismatched = CourseSession::query()
                ->whereIn('id', $prereqIds)
                ->get(['id', 'course_id'])
                ->filter(fn (CourseSession $s) => (int) $s->course_id !== $courseId)
                ->pluck('id')
                ->all();

            if ($mismatched !== []) {
                $v->errors()->add('prerequisite_session_ids', 'Prerequisite sessions must belong to the same course as this session.');
            }
        });
    }

    /**
     * @return array<int>
     */
    private function prereqIds(): array
    {
        $ids = $this->input('prerequisite_session_ids', []);
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => is_numeric($v) ? (int) $v : null,
            $ids,
        ))));
    }
}
