<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MissionaryMemory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MissionaryMemoryService
{
    public function __construct(private readonly MediaService $mediaService) {}

    /**
     * @param  array<string, mixed>  $filters  title, missionary_request_id, from_date, to_date
     */
    public function listForMissionary(User $missionary, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return MissionaryMemory::query()
            ->with(['media', 'missionaryRequest'])
            ->where('missionary_id', $missionary->id)
            ->when($filters['title'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters['missionary_request_id'] ?? null, fn ($q, $v) => $q->where('missionary_request_id', $v))
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(User $missionary, array $data): MissionaryMemory
    {
        $mediaIds = Arr::get($data, 'media_ids', []);

        return DB::transaction(function () use ($missionary, $data, $mediaIds) {
            $memory = MissionaryMemory::create([
                'missionary_id' => $missionary->id,
                'missionary_request_id' => $data['missionary_request_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            $this->attachMedia($memory, $mediaIds, $missionary);

            return $memory->load(['media', 'missionaryRequest']);
        });
    }

    public function update(MissionaryMemory $memory, User $missionary, array $data): MissionaryMemory
    {
        $mediaIds = Arr::get($data, 'media_ids', []);

        return DB::transaction(function () use ($memory, $missionary, $data, $mediaIds) {
            $memory->fill(Arr::only($data, ['title', 'description', 'missionary_request_id']))->save();

            $this->attachMedia($memory, $mediaIds, $missionary);

            return $memory->load(['media', 'missionaryRequest']);
        });
    }

    public function delete(MissionaryMemory $memory): void
    {
        DB::transaction(function () use ($memory) {
            foreach ($memory->media()->get() as $medium) {
                $this->mediaService->delete($medium);
            }
            $memory->delete();
        });
    }

    /**
     * @param  array<int, int|string>  $mediaIds
     */
    private function attachMedia(Model $owner, array $mediaIds, User $uploader): void
    {
        if (empty($mediaIds)) {
            return;
        }

        foreach ($mediaIds as $id) {
            $media = Media::findOrFail((int) $id);
            $this->mediaService->attachTo(
                media: $media,
                owner: $owner,
                uploader: $uploader,
                collection: $media->collection_name,
                singleFile: false,
            );
        }
    }
}
