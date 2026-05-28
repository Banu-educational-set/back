<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'title' => $this->title,
            'description' => $this->description,
            'score' => (int) $this->score,
            'minimum_score' => (int) $this->minimum_score,
            'duration_minutes' => $this->duration_minutes,
            'is_random' => (bool) $this->is_random,
            'submitters_count' => $this->when(isset($this->submitters_count), (int) $this->submitters_count),
            'questions_count' => $this->when(isset($this->questions_count), (int) $this->questions_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'session' => new CourseSessionResource($this->whenLoaded('session')),
            'questions' => ExamQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
