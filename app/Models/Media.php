<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'model_type', 'model_id',
        'collection_name', 'file_name', 'file_path',
        'mime_type', 'file_size', 'disk',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPending(): bool
    {
        return $this->model_id === null;
    }

    /**
     * Public URL for the file. Returns null when the disk does not expose
     * direct URLs (e.g. the default 'local' disk). Use downloadUrl() for those.
     */
    public function url(): ?string
    {
        try {
            return Storage::disk($this->disk)->url($this->file_path);
        } catch (\Throwable) {
            return null;
        }
    }

    public function downloadUrl(): string
    {
        return route('media.download', ['medium' => $this->id]);
    }
}
