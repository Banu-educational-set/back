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
        $paginator = Course::query()
            ->with(['term', 'teacher.avatar', 'cover'])
            ->withCount('sessions')
            ->when($termId, fn ($q, $id) => $q->where('term_id', $id))
            ->when($orphansOnly, fn ($q) => $q->whereNull('term_id'))
            ->when(
                $this->mustFilterInactive($viewer),
                fn ($q) => $q->where('is_active', true)
            )
            ->orderByDesc('id')
            ->paginate($perPage);

        $this->attachPrerequisiteCourses($paginator->getCollection());

        return $paginator;
    }

    /**
     * Hydrate each course in the collection with its prerequisite_courses
     * relation, batching the load to avoid N+1.
     *
     * @param  \Illuminate\Support\Collection<int, Course>|iterable<Course>  $courses
     */
    public function attachPrerequisiteCourses($courses): void
    {
        $allIds = $courses
            ->flatMap(fn (Course $c) => is_array($c->prerequisite_course_ids) ? $c->prerequisite_course_ids : [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $byId = $allIds->isEmpty()
            ? collect()
            : Course::query()
                ->whereIn('id', $allIds)
                ->with('cover')
                ->withCount('sessions')
                ->get()
                ->keyBy('id');

        foreach ($courses as $course) {
            $ids = is_array($course->prerequisite_course_ids) ? $course->prerequisite_course_ids : [];
            $course->setRelation(
                'prerequisiteCourses',
                collect($ids)->map(fn ($id) => $byId->get((int) $id))->filter()->values(),
            );
        }
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
