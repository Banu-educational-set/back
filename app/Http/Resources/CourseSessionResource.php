<?php

namespace App\Http\Resources;

use App\Enums\RoleName;
use App\Http\Resources\MediaResource;
use App\Services\GradeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'location' => $this->location,
            'link' => $this->link,
            'prerequisite_session_ids' => array_values(array_map('intval', $this->prerequisite_session_ids ?? [])),
            'prerequisite_sessions' => CourseSessionResource::collection($this->whenLoaded('prerequisiteSessions')),
            'exams_count' => $this->when(isset($this->exams_count), (int) $this->exams_count),
            'homeworks_count' => $this->when(isset($this->homeworks_count), (int) $this->homeworks_count),
            'your_average' => $this->when(
                $this->isStudentRequester($request),
                fn () => $this->roundOrNull(app(GradeService::class)->studentSessionAverage($request->user(), $this->resource)),
            ),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'course' => new CourseResource($this->whenLoaded('course')),
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
