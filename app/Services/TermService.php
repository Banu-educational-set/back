<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Term;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TermService
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function paginate(?bool $activeOnly, int $perPage = 20): LengthAwarePaginator
    {
        return Term::query()
            ->with('cover')
            ->withCount(['courses', 'enrollments'])
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data, ?User $uploader = null): Term
    {
        $coverMediaId = Arr::pull($data, 'cover_media_id');

        return DB::transaction(function () use ($data, $coverMediaId, $uploader) {
            $term = Term::create($data);
            $this->attachCover($term, $coverMediaId, $uploader);

            return $term;
        });
    }

    public function update(Term $term, array $data, ?User $uploader = null): Term
    {
        $coverMediaId = Arr::pull($data, 'cover_media_id');

        return DB::transaction(function () use ($term, $data, $coverMediaId, $uploader) {
            $term->fill($data)->save();
            $this->attachCover($term, $coverMediaId, $uploader);

            return $term;
        });
    }

    public function delete(Term $term): void
    {
        $term->delete();
    }

    private function attachCover(Term $term, ?int $mediaId, ?User $uploader): void
    {
        if ($mediaId === null || $uploader === null) {
            return;
        }

        $media = Media::findOrFail($mediaId);
        $this->mediaService->attachTo(
            media: $media,
            owner: $term,
            uploader: $uploader,
            collection: 'cover',
            singleFile: true,
        );
    }
}
