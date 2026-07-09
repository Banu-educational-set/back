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
            'national_code' => $this->national_code,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'province' => new ProvinceResource($this->whenLoaded('province')),
            'city' => new CityResource($this->whenLoaded('city')),
            'marriage_status' => $this->marriage_status,
            'birthday' => $this->birthday?->toDateString(),
            'gender' => $this->gender,
            'address' => $this->address,
            'bio' => $this->bio,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'block_reason' => $this->block_reason,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'avatar_url' => $this->avatar?->url(),
            'avatar_download_url' => $this->avatar?->downloadUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
