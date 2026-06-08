<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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
        $config = (array) config("filesystems.disks.{$this->disk}", []);
        $visibility = $config['visibility'] ?? null;
        $serve = (bool) ($config['serve'] ?? false);

        try {
            if ($visibility === 'public') {
                // Public disk — direct static URL (served by web server).
                $url = Storage::disk($this->disk)->url($this->file_path);
            } elseif ($serve) {
                // Local disk with serve=true — generate a signed URL on the
                // framework's storage.{disk} route. Required because the
                // ServeFile controller rejects unsigned requests with 404
                // for non-public disks.
                $url = URL::temporarySignedRoute(
                    'storage.'.$this->disk,
                    now()->addMinutes(60),
                    ['path' => $this->file_path],
                    absolute: false,
                );
            } else {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        if ($url === null || $url === '') {
            return null;
        }
        if (! preg_match('#^https?://#i', $url)) {
            return URL::to($url);
        }

        return $url;
    }

    public function downloadUrl(): string
    {
        return route('media.download', ['medium' => $this->id]);
    }
}
