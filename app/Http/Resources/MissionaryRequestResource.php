<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionaryRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'missionary_id' => $this->missionary_id,
            'external_source' => $this->external_source,
            'external_reference_id' => $this->external_reference_id,
            'requester_name' => $this->requester_name,
            'requester_phone' => $this->requester_phone,
            'title' => $this->title,
            'subject' => $this->subject,
            'description' => $this->description,
            'location' => $this->location,
            'requested_date' => $this->requested_date?->toDateString(),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
