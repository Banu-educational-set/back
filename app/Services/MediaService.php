<?php

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MediaService
{
    /**
     * Store an uploaded file as a pending media row owned by no entity yet.
     * The row's collection_name records the upload purpose so it can later be
     * attached only to a compatible owner collection.
     */
    public function upload(UploadedFile $file, string $purpose, User $uploader): Media
    {
        $config = $this->purposeConfig($purpose);

        $disk = $config['disk'];
        $directory = $this->buildPendingDirectory($uploader);
        $stored = $file->storeAs(
            $directory,
            $this->buildFilename($file),
            ['disk' => $disk],
        );

        return Media::create([
            'collection_name' => $config['collection'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $stored,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'disk' => $disk,
            'uploaded_by' => $uploader->id,
        ]);
    }

    /**
     * Promote a pending media row to be attached to a specific owner entity.
     * The pending row must belong to $uploader and target $collection. Files
     * are moved into the owner's directory; for single-file collections, any
     * existing media in that collection on the owner is deleted first.
     */
    public function attachTo(
        Media $media,
        Model $owner,
        User $uploader,
        string $collection,
        bool $singleFile = false,
    ): Media {
        // Idempotent: if the media is already attached to this exact owner
        // and collection, the update payload simply re-listed an existing file.
        // Return it unchanged instead of erroring.
        if ($media->model_type === $owner->getMorphClass()
            && (int) $media->model_id === (int) $owner->getKey()
            && $media->collection_name === $collection) {
            return $media;
        }

        if (! $media->isPending()) {
            throw new RuntimeException(__('errors.media_already_attached'));
        }

        if ($media->uploaded_by !== $uploader->id) {
            throw new RuntimeException(__('errors.media_not_owned'));
        }

        if ($media->collection_name !== $collection) {
            throw new RuntimeException(__('errors.media_purpose_mismatch'));
        }

        if ($singleFile) {
            $owner->media()->where('collection_name', $collection)->get()->each(
                fn (Media $existing) => $this->delete($existing),
            );
        }

        $newDirectory = $this->buildDirectory($owner, $collection);
        $newPath = $newDirectory.'/'.basename($media->file_path);

        Storage::disk($media->disk)->move($media->file_path, $newPath);

        $media->forceFill([
            'model_type' => $owner->getMorphClass(),
            'model_id' => $owner->getKey(),
            'collection_name' => $collection,
            'file_path' => $newPath,
        ])->save();

        return $media->fresh();
    }

    public function delete(Media $media): void
    {
        Storage::disk($media->disk)->delete($media->file_path);
        $media->delete();
    }

    /**
     * Delete pending media rows whose attach never happened. Each row's file
     * is removed from disk before the row is deleted.
     */
    public function deleteOrphans(int $olderThanHours = 24): int
    {
        $cutoff = now()->subHours($olderThanHours);

        $count = 0;
        Media::query()
            ->whereNull('model_id')
            ->where('created_at', '<', $cutoff)
            ->chunkById(100, function ($rows) use (&$count) {
                foreach ($rows as $media) {
                    $this->delete($media);
                    $count++;
                }
            });

        return $count;
    }

    public function purposeConfig(string $purpose): array
    {
        $config = config("education.media.purposes.{$purpose}");

        if (! is_array($config)) {
            throw new RuntimeException(__('errors.unknown_media_purpose', ['purpose' => $purpose]));
        }

        return $config;
    }

    private function buildDirectory(Model $owner, string $collection): string
    {
        $folder = Str::of(class_basename($owner))->snake()->plural();

        return "{$folder}/{$owner->getKey()}/{$collection}";
    }

    private function buildPendingDirectory(User $uploader): string
    {
        return "pending/{$uploader->id}";
    }

    private function buildFilename(UploadedFile $file): string
    {
        $name = Str::of($file->getClientOriginalName())->beforeLast('.')->slug();
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';

        return $name.'-'.Str::random(8).'.'.$ext;
    }
}
