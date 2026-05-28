<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TermResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'score' => (int) $this->score,
            'minimum_score' => (int) $this->minimum_score,
            'cover_url' => $this->cover?->url(),
            'courses_count' => $this->when(isset($this->courses_count), (int) $this->courses_count),
            'students_count' => $this->when(isset($this->enrollments_count), (int) $this->enrollments_count),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
