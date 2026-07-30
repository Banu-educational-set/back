<?php

namespace App\Services;

use App\Enums\MissionaryRequestStatus;
use App\Enums\RoleName;
use App\Models\MissionaryRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

class MissionaryRequestService
{
    public function createFromExternal(array $data): MissionaryRequest
    {
        return MissionaryRequest::create([
            'missionary_id' => $data['missionary_id'] ?? null,
            'external_source' => $data['external_source'] ?? 'wordpress',
            'external_reference_id' => $data['external_reference_id'] ?? null,
            'requester_name' => $data['requester_name'],
            'requester_phone' => $data['requester_phone'] ?? null,
            'title' => $data['title'],
            'subject' => $data['subject'] ?? null,
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'requested_date' => $data['requested_date'] ?? null,
            'status' => MissionaryRequestStatus::Pending->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters  status, requester_name, title, subject, from_date, to_date
     */
    public function listForMissionary(User $missionary, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        // Missionaries see requests assigned to them plus unassigned ones
        // (which they implicitly claim by being the first to update status).
        $query = MissionaryRequest::query()
            ->where(function ($q) use ($missionary) {
                $q->where('missionary_id', $missionary->id)
                  ->orWhereNull('missionary_id');
            });

        $this->applyRequestFilters($query, $filters);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Apply the shared missionary-request list filters.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyRequestFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['requester_name'] ?? null, fn ($q, $v) => $q->where('requester_name', 'like', "%{$v}%"))
            ->when($filters['title'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters['subject'] ?? null, fn ($q, $v) => $q->where('subject', 'like', "%{$v}%"))
            ->when($filters['missionary_id'] ?? null, fn ($q, $v) => $q->where('missionary_id', $v))
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    /**
     * Admins see every request regardless of assignment.
     */
    /**
     * @param  array<string, mixed>  $filters  status, requester_name, title, subject, missionary_id, from_date, to_date
     */
    public function listAll(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = MissionaryRequest::query()->with('missionary');

        $this->applyRequestFilters($query, $filters);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function updateStatus(User $missionary, MissionaryRequest $request, string $status): MissionaryRequest
    {
        if (! $missionary->hasRole(RoleName::Missionary->value)) {
            throw new RuntimeException(__('errors.missionary_only_update'));
        }

        if ($request->missionary_id !== null && $request->missionary_id !== $missionary->id) {
            throw new RuntimeException(__('errors.request_assigned_other'));
        }

        $current = $request->status instanceof MissionaryRequestStatus
            ? $request->status->value
            : (string) $request->status;

        if (! in_array($current, MissionaryRequestStatus::missionarySources(), true)) {
            throw new RuntimeException(__('errors.missionary_source_states'));
        }

        if (! in_array($status, MissionaryRequestStatus::missionaryTargets(), true)) {
            throw new RuntimeException(__('errors.missionary_target_states'));
        }

        $request->status = $status;
        if ($request->missionary_id === null) {
            $request->missionary_id = $missionary->id;
        }
        $request->save();

        return $request->fresh();
    }

    /**
     * Admins may move a request from any status to any other status, without
     * changing its missionary assignment.
     */
    public function updateStatusByAdmin(MissionaryRequest $request, string $status): MissionaryRequest
    {
        if (! in_array($status, MissionaryRequestStatus::values(), true)) {
            throw new RuntimeException(__('errors.invalid_status'));
        }

        $request->status = $status;
        $request->save();

        return $request->fresh();
    }
}
