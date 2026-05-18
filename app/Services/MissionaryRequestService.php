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
            'requester_email' => $data['requester_email'] ?? null,
            'title' => $data['title'],
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

    public function updateStatus(User $missionary, MissionaryRequest $request, string $status): MissionaryRequest
    {
        if (! $missionary->hasRole(RoleName::Missionary->value)) {
            throw new RuntimeException('Only missionaries can update requests.');
        }

        if ($request->missionary_id !== null && $request->missionary_id !== $missionary->id) {
            throw new RuntimeException('This request is assigned to another missionary.');
        }

        if (! in_array($status, MissionaryRequestStatus::missionaryAssignable(), true)) {
            throw new RuntimeException('Status not allowed for missionary updates.');
        }

        $request->status = $status;
        if ($request->missionary_id === null) {
            $request->missionary_id = $missionary->id;
        }
        $request->save();

        return $request->fresh();
    }
}
