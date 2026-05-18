<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TermEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'term_id' => $this->term_id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'term' => new TermResource($this->whenLoaded('term')),
        ];
    }
}
