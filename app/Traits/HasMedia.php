<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function mediaIn(string $collection): MorphMany
    {
        return $this->media()->where('collection_name', $collection);
    }

    public function getFirstMedia(string $collection): ?Media
    {
        return $this->media()->where('collection_name', $collection)->latest('id')->first();
    }

    public function getFirstMediaUrl(string $collection): ?string
    {
        return $this->getFirstMedia($collection)?->url();
    }
}
