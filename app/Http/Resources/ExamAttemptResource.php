<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'score' => $this->score,
            'is_passed' => $this->is_passed,
            'correct_count' => $this->when(isset($this->correct_count), (int) $this->correct_count),
            'total_questions' => $this->when(isset($this->total_questions), (int) $this->total_questions),
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'deadline_at' => $this->deadline_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
