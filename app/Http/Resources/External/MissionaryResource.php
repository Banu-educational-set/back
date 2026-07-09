<?php

namespace App\Http\Resources\External;

use App\Http\Resources\CityResource;
use App\Http\Resources\MissionaryMemoryResource;
use App\Http\Resources\ProvinceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'bio' => $this->bio,
            'province' => new ProvinceResource($this->whenLoaded('province')),
            'city' => new CityResource($this->whenLoaded('city')),
            'avatar_url' => $this->avatar?->url(),
            'memories' => MissionaryMemoryResource::collection($this->whenLoaded('memories')),
        ];
    }
}
