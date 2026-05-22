<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Course;
use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function paginate(?User $viewer, ?int $termId, bool $orphansOnly = false, int $perPage = 20): LengthAwarePaginator
    {
        return Course::query()
            ->with(['term', 'teacher.avatar', 'cover'])
            ->when($termId, fn ($q, $id) => $q->where('term_id', $id))
            ->when($orphansOnly, fn ($q) => $q->whereNull('term_id'))
            ->when(
                $this->mustFilterInactive($viewer),
                fn ($q) => $q->where('is_active', true)
            )
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data, ?User $uploader = null): Course
    {
        $coverMediaId = Arr::pull($data, 'cover_media_id');

        return DB::transaction(function () use ($data, $coverMediaId, $uploader) {
            $course = Course::create($data);
            $this->attachCover($course, $coverMediaId, $uploader);

            return $course->load(['term', 'teacher']);
        });
    }

    public function update(Course $course, array $data, ?User $uploader = null): Course
    {
        $coverMediaId = Arr::pull($data, 'cover_media_id');

        return DB::transaction(function () use ($course, $data, $coverMediaId, $uploader) {
            $course->fill($data)->save();
            $this->attachCover($course, $coverMediaId, $uploader);

            return $course->load(['term', 'teacher']);
        });
    }

    public function delete(Course $course): void
    {
        $course->delete();
    }

    private function attachCover(Course $course, ?int $mediaId, ?User $uploader): void
    {
        if ($mediaId === null || $uploader === null) {
            return;
        }

        $media = Media::findOrFail($mediaId);
        $this->mediaService->attachTo(
            media: $media,
            owner: $course,
            uploader: $uploader,
            collection: 'cover',
            singleFile: true,
        );
    }

    private function mustFilterInactive(?User $viewer): bool
    {
        if (! $viewer) {
            return true;
        }

        return ! $viewer->hasAnyRole([
            RoleName::Admin->value,
            RoleName::Teacher->value,
        ]);
    }
}
