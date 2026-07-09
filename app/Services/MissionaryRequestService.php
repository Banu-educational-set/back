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

    public function listForMissionary(User $missionary): LengthAwarePaginator
    {
        // Missionaries see requests assigned to them plus unassigned ones
        // (which they implicitly claim by being the first to update status).
        return MissionaryRequest::query()
            ->where(function ($q) use ($missionary) {
                $q->where('missionary_id', $missionary->id)
                  ->orWhereNull('missionary_id');
            })
            ->orderByDesc('id')
            ->paginate(20);
    }

    /**
     * Admins see every request regardless of assignment.
     */
    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return MissionaryRequest::query()
            ->with('missionary')
            ->orderByDesc('id')
            ->paginate($perPage);
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
