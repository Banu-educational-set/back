<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeworkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'title' => $this->title,
            'description' => $this->description,
            'deadline' => $this->deadline?->toIso8601String(),
            'effective_deadline' => $this->effectiveDeadline()?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_priority' => $this->is_priority,
            'submitters_count' => $this->when(isset($this->submitters_count), (int) $this->submitters_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'session' => new CourseSessionResource($this->whenLoaded('session')),
        ];
    }
}
