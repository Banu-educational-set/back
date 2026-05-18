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
            'is_active' => $this->is_active,
        ];
    }
}
