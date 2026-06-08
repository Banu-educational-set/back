<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'type' => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'category' => $this->category,
            'priority' => $this->priority instanceof \BackedEnum ? $this->priority->value : $this->priority,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'subject' => $this->subject,
            'created_at' => $this->created_at?->toIso8601String(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'student' => new UserResource($this->whenLoaded('student')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'messages' => TicketMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
