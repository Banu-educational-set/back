<?php

namespace App\Services;

use App\Enums\TicketType;
use App\Models\Ticket;
use App\Models\User;

class CounselorDashboardService
{
    private const RECENT_LIMIT = 5;

    public function __construct(private readonly TicketService $ticketService) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $counselor): array
    {
        // Counselors are scoped to advise tickets — statsForStaff already
        // locks a counselor's scope to type=advise and returns the per-status
        // summary { open_tickets, answered_tickets, closed_tickets, all }.
        $stats = $this->ticketService->statsForStaff($counselor);

        return [
            'profile' => [
                'id' => $counselor->id,
                'name' => $counselor->name,
                'avatar_url' => $counselor->loadMissing('avatar')->avatar?->url(),
                'province' => $counselor->loadMissing('province')->province?->name,
                'city' => $counselor->loadMissing('city')->city?->name,
                'role' => $counselor->getRoleNames()->first(),
                'membership_days' => (int) $counselor->created_at?->diffInDays(now()),
            ],
            'stats' => [
                'open_tickets' => $stats['open_tickets'],
                'answered_tickets' => $stats['answered_tickets'],
                'closed_tickets' => $stats['closed_tickets'],
                'total_tickets' => $stats['all'],
                'assigned_to_me' => Ticket::where('type', TicketType::Advise->value)
                    ->where('assigned_to_user_id', $counselor->id)->count(),
            ],
            'recent_tickets' => $this->recentTickets(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentTickets(): array
    {
        return Ticket::query()
            ->where('type', TicketType::Advise->value)
            ->with('student:id,name')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (Ticket $t) => [
                'id' => $t->id,
                'student_id' => $t->student_id,
                'student_name' => $t->student?->name,
                'subject' => $t->subject,
                'status' => $t->status instanceof \BackedEnum ? $t->status->value : $t->status,
                'created_at' => $t->created_at?->toIso8601String(),
            ])
            ->all();
    }
}
