<?php

namespace App\Http\Resources;

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
            'term' => new TermResource($this->whenLoaded('term')),
            'teacher' => new UserResource($this->whenLoaded('teacher')),
        ];
    }
}
