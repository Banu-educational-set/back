<?php

namespace App\Http\Resources;

use App\Http\Resources\MediaResource;
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
            'location' => $this->location,
            'link' => $this->link,
            'prerequisite_session_ids' => array_values(array_map('intval', $this->prerequisite_session_ids ?? [])),
            'prerequisite_sessions' => CourseSessionResource::collection($this->whenLoaded('prerequisiteSessions')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'course' => new CourseResource($this->whenLoaded('course')),
        ];
    }
}
