<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'avatar_url' => $this->avatar?->url(),
            'avatar_download_url' => $this->avatar?->downloadUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
