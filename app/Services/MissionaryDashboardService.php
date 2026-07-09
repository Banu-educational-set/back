<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\MissionaryRequestStatus;
use App\Models\MissionaryMemory;
use App\Models\MissionaryRequest;
use App\Models\TermEnrollment;
use App\Models\User;

class MissionaryDashboardService
{
    private const RECENT_LIMIT = 5;

    /**
     * @return array<string, mixed>
     */
    public function build(User $missionary): array
    {
        $assigned = MissionaryRequest::where('missionary_id', $missionary->id);

        return [
            'profile' => [
                'id' => $missionary->id,
                'name' => $missionary->name,
                'avatar_url' => $missionary->loadMissing('avatar')->avatar?->url(),
                'province' => $missionary->loadMissing('province')->province?->name,
                'city' => $missionary->loadMissing('city')->city?->name,
                'role' => $missionary->getRoleNames()->first(),
                'membership_days' => (int) $missionary->created_at?->diffInDays(now()),
            ],
            'stats' => [
                'total_requests' => (clone $assigned)->count(),
                'pending_requests' => (clone $assigned)->whereIn('status', MissionaryRequestStatus::missionarySources())->count(),
                'accepted_requests' => (clone $assigned)->where('status', MissionaryRequestStatus::Accepted->value)->count(),
                'rejected_requests' => (clone $assigned)->where('status', MissionaryRequestStatus::Rejected->value)->count(),
                'unassigned_requests' => MissionaryRequest::whereNull('missionary_id')->count(),
                'memories_count' => MissionaryMemory::where('missionary_id', $missionary->id)->count(),
                'completed_terms' => TermEnrollment::where('user_id', $missionary->id)
                    ->where('status', EnrollmentStatus::Completed->value)->count(),
            ],
            'recent_requests' => $this->recentRequests($missionary),
            'recent_memories' => $this->recentMemories($missionary),
        ];
    }

    /**
     * Assigned-to-me plus unassigned (claimable) requests, newest first —
     * mirrors MissionaryRequestService::listForMissionary's visibility.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentRequests(User $missionary): array
    {
        return MissionaryRequest::query()
            ->where(fn ($q) => $q->where('missionary_id', $missionary->id)->orWhereNull('missionary_id'))
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (MissionaryRequest $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'subject' => $r->subject,
                'status' => $r->status instanceof \BackedEnum ? $r->status->value : $r->status,
                'requester_name' => $r->requester_name,
                'created_at' => $r->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentMemories(User $missionary): array
    {
        return MissionaryMemory::query()
            ->where('missionary_id', $missionary->id)
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (MissionaryMemory $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'created_at' => $m->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
