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
            'pass_score' => $this->effectivePassScore(),
            'questions' => ExamQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
