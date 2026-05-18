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
            'submitted_at' => $this->submitted_at?->toIso8601String(),
        ];
    }
}
