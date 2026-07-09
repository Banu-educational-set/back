<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionaryMemoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'missionary_id' => $this->missionary_id,
            'missionary_request_id' => $this->missionary_request_id,
            'title' => $this->title,
            'description' => $this->description,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'missionary_request' => new MissionaryRequestResource($this->whenLoaded('missionaryRequest')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
