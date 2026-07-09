<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\Ticket;
use App\Models\TermEnrollment;
use App\Models\User;

class AdminDashboardService
{
    private const RECENT_LIMIT = 5;

    /**
     * Build the aggregated admin dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function build(User $admin): array
    {
        return [
            'profile' => $this->profile($admin),
            'stats' => [
                'active_users' => User::where('status', UserStatus::Approved->value)->count(),
                'total_users' => User::count(),
                'courses_created' => Course::count(),
                'courses_sold' => TermEnrollment::count(),
            ],
            'recent_registrations' => $this->recentRegistrations(),
            'recent_advise_requests' => $this->recentTickets(TicketType::Advise),
            'recent_service_requests' => $this->recentTickets(TicketType::Service),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(User $admin): array
    {
        $admin->loadMissing(['avatar', 'province', 'city']);

        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'avatar_url' => $admin->avatar?->url(),
            'province' => $admin->province?->name,
            'city' => $admin->city?->name,
            'role' => $admin->getRoleNames()->first(),
            'roles' => $admin->getRoleNames()->values(),
            // The admin dashboard is admin-only, so access is always full.
            'access_level' => 'full',
            'open_tickets' => Ticket::where('status', TicketStatus::Open->value)->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentRegistrations(): array
    {
        return User::query()
            ->with('avatar')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar?->url(),
                'registered_at' => $u->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTickets(TicketType $type): array
    {
        return Ticket::query()
            ->where('type', $type->value)
            ->with('student:id,name')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (Ticket $t) => [
                'id' => $t->id,
                'student_id' => $t->student_id,
                'student_name' => $t->student?->name,
                'subject' => $t->subject,
                'created_at' => $t->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
