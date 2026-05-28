<?php

namespace App\Services;

use App\Models\Homework;
use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class HomeworkService
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function paginate(?int $sessionId, ?int $courseId, ?int $termId, int $perPage = 20): LengthAwarePaginator
    {
        return Homework::query()
            ->with(['session.course.term', 'media'])
            ->withCount(['submissions as submitters_count'])
            ->when($sessionId, fn ($q, $id) => $q->where('session_id', $id))
            ->when($courseId, fn ($q, $id) => $q->whereHas('session', fn ($s) => $s->where('course_id', $id)))
            ->when($termId, fn ($q, $id) => $q->whereHas('session.course', fn ($c) => $c->where('term_id', $id)))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data, ?User $uploader = null): Homework
    {
        $mediaIds = Arr::pull($data, 'media_ids', []);

        return DB::transaction(function () use ($data, $mediaIds, $uploader) {
            $homework = Homework::create($data);
            $this->attachMedia($homework, $mediaIds, $uploader);

            return $homework->load('media');
        });
    }

    public function update(Homework $homework, array $data, ?User $uploader = null): Homework
    {
        $mediaIds = Arr::pull($data, 'media_ids', []);

        return DB::transaction(function () use ($homework, $data, $mediaIds, $uploader) {
            $homework->fill($data)->save();
            $this->attachMedia($homework, $mediaIds, $uploader);

            return $homework->fresh('media');
        });
    }

    public function delete(Homework $homework): void
    {
        $homework->delete();
    }

    private function attachMedia(Homework $homework, array $mediaIds, ?User $uploader): void
    {
        if ($uploader === null || empty($mediaIds)) {
            return;
        }

        foreach ($mediaIds as $id) {
            $media = Media::findOrFail((int) $id);
            $this->mediaService->attachTo(
                media: $media,
                owner: $homework,
                uploader: $uploader,
                collection: $media->collection_name,
                singleFile: false,
            );
        }
    }
}
