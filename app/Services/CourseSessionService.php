<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\CourseSession;
use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CourseSessionService
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function paginate(?User $viewer, ?int $courseId, int $perPage = 20): LengthAwarePaginator
    {
        $paginator = CourseSession::query()
            ->with('course.term')
            ->withCount(['exams', 'homeworks'])
            ->when($courseId, fn ($q, $id) => $q->where('course_id', $id))
            ->when(
                $this->mustFilterInactive($viewer),
                fn ($q) => $q->whereHas('course', fn ($c) => $c
                    ->where('is_active', true)
                    ->whereHas('term', fn ($t) => $t->openNow()))
            )
            ->orderByDesc('id')
            ->paginate($perPage);

        $this->attachPrerequisiteSessions($paginator->getCollection());

        return $paginator;
    }

    /**
     * Hydrate each session in the collection with its prerequisite_sessions
     * relation, batching the load to avoid N+1.
     *
     * @param  \Illuminate\Support\Collection<int, CourseSession>|iterable<CourseSession>  $sessions
     */
    public function attachPrerequisiteSessions($sessions): void
    {
        $allIds = collect($sessions)
            ->flatMap(fn (CourseSession $s) => is_array($s->prerequisite_session_ids) ? $s->prerequisite_session_ids : [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $byId = $allIds->isEmpty()
            ? collect()
            : CourseSession::query()
                ->whereIn('id', $allIds)
                ->with('course')
                ->get()
                ->keyBy('id');

        foreach ($sessions as $session) {
            $ids = is_array($session->prerequisite_session_ids) ? $session->prerequisite_session_ids : [];
            $session->setRelation(
                'prerequisiteSessions',
                collect($ids)->map(fn ($id) => $byId->get((int) $id))->filter()->values(),
            );
        }
    }

    public function create(array $data, ?User $uploader = null): CourseSession
    {
        $mediaIds = Arr::pull($data, 'media_ids', []);

        return DB::transaction(function () use ($data, $mediaIds, $uploader) {
            $session = CourseSession::create($data);
            $this->attachMedia($session, $mediaIds, $uploader);

            return $session->load('course');
        });
    }

    public function update(CourseSession $session, array $data, ?User $uploader = null): CourseSession
    {
        $mediaIds = Arr::pull($data, 'media_ids', []);

        return DB::transaction(function () use ($session, $data, $mediaIds, $uploader) {
            $session->fill($data)->save();
            $this->attachMedia($session, $mediaIds, $uploader);

            return $session->load('course');
        });
    }

    public function delete(CourseSession $session): void
    {
        $session->delete();
    }

    /**
     * Attach each pending media row to the session. The collection comes from
     * the media row itself — set when the file was uploaded with its purpose.
     */
    private function attachMedia(CourseSession $session, array $mediaIds, ?User $uploader): void
    {
        if ($uploader === null || empty($mediaIds)) {
            return;
        }

        foreach ($mediaIds as $id) {
            $media = Media::findOrFail((int) $id);
            $this->mediaService->attachTo(
                media: $media,
                owner: $session,
                uploader: $uploader,
                collection: $media->collection_name,
                singleFile: false,
            );
        }
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
