<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collection_name' => $this->purposeForCollection($this->collection_name) ?? $this->collection_name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'url' => $this->url(),
            'download_url' => $this->downloadUrl(),
        ];
    }

    /**
     * Reverse-lookup: given a stored collection_name (e.g. "voices",
     * "submission_files"), return the purpose key that maps to it
     * (e.g. "voice", "homework_file"). Null when no purpose matches.
     */
    private function purposeForCollection(?string $collection): ?string
    {
        if ($collection === null) {
            return null;
        }
        foreach ((array) config('education.media.purposes', []) as $purpose => $cfg) {
            if (($cfg['collection'] ?? null) === $collection) {
                return (string) $purpose;
            }
        }

        return null;
    }
}
