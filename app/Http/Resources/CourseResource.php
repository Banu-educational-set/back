<?php

namespace App\Http\Resources;

use App\Enums\RoleName;
use App\Services\GradeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'term_id' => $this->term_id,
            'teacher_id' => $this->teacher_id,
            'title' => $this->title,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'is_active' => $this->is_active,
            'cover_url' => $this->cover?->url(),
            'sessions_count' => $this->when(isset($this->sessions_count), (int) $this->sessions_count),
            'prerequisite_course_ids' => array_values(array_map('intval', $this->prerequisite_course_ids ?? [])),
            'prerequisite_courses' => CourseResource::collection($this->whenLoaded('prerequisiteCourses')),
            'your_average' => $this->when(
                $this->isStudentRequester($request),
                fn () => $this->roundOrNull(app(GradeService::class)->studentCourseAverage($request->user(), $this->resource)),
            ),
            'term' => new TermResource($this->whenLoaded('term')),
            'teacher' => new UserResource($this->whenLoaded('teacher')),
        ];
    }

    private function isStudentRequester(Request $request): bool
    {
        $user = $request->user();

        return $user
            && $user->hasRole(RoleName::Student->value)
            && ! $user->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]);
    }

    private function roundOrNull(?float $v): ?float
    {
        return $v === null ? null : round($v, 2);
    }
}
