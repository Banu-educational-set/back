<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'sender_id' => $this->sender_id,
            'message' => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
            'sender' => new UserResource($this->whenLoaded('sender')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
