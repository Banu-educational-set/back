<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeworkSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'homework_id' => $this->homework_id,
            'user_id' => $this->user_id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'teacher_feedback' => $this->teacher_feedback,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
