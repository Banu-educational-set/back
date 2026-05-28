<?php

namespace App\Http\Resources;

use App\Enums\RoleName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_text' => $this->option_text,
            'is_correct' => $this->when(
                $request->user()?->hasAnyRole([RoleName::Admin->value, RoleName::Teacher->value]) ?? false,
                fn () => (bool) $this->is_correct,
            ),
        ];
    }
}
