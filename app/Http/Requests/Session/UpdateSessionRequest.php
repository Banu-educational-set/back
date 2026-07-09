<?php

namespace App\Http\Requests\Session;

use App\Enums\SessionType;
use App\Models\CourseSession;
use App\Services\PrerequisiteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('session')) ?? false;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', Rule::in(SessionType::values())],
            'starts_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'location' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:500'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'prerequisite_session_ids' => ['sometimes', 'nullable', 'array'],
            'prerequisite_session_ids.*' => ['integer', 'distinct', 'exists:course_sessions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (! $this->has('prerequisite_session_ids')) {
            return;
        }

        $validator->after(function (Validator $v) {
            /** @var CourseSession $session */
            $session = $this->route('session');
            $prereqIds = $this->prereqIds();

            if ($prereqIds === []) {
                return;
            }

            if (in_array($session->id, $prereqIds, true)) {
                $v->errors()->add('prerequisite_session_ids', __('errors.session_self_prereq'));

                return;
            }

            $courseId = $this->has('course_id') ? (int) $this->input('course_id') : (int) $session->course_id;
            $mismatched = CourseSession::query()
                ->whereIn('id', $prereqIds)
                ->get(['id', 'course_id'])
                ->filter(fn (CourseSession $s) => (int) $s->course_id !== $courseId)
                ->pluck('id')
                ->all();

            if ($mismatched !== []) {
                $v->errors()->add('prerequisite_session_ids', __('errors.session_prereq_same_course'));
            }

            if (app(PrerequisiteService::class)->wouldCreateSessionCycle($session->id, $prereqIds)) {
                $v->errors()->add('prerequisite_session_ids', __('errors.session_prereq_cycle'));
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
