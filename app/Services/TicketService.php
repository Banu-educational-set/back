<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Enums\TicketTargetRole;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function createForStudent(User $student, array $data): Ticket
    {
        return DB::transaction(function () use ($student, $data) {
            $ticket = Ticket::create([
                'student_id' => $student->id,
                'target_role' => $data['target_role'],
                'subject' => $data['subject'],
                'status' => TicketStatus::Open->value,
            ]);

            if (! empty($data['message'])) {
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'sender_id' => $student->id,
                    'message' => $data['message'],
                ]);
            }

            return $ticket->load('messages');
        });
    }

    public function listForStudent(User $student): LengthAwarePaginator
    {
        return Ticket::query()
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function listForStaff(User $staff, ?TicketTargetRole $scope = null): LengthAwarePaginator
    {
        $targetRoles = $scope
            ? [$scope->value]
            : $this->targetRolesForStaff($staff);

        return Ticket::query()
            ->with(['student', 'assignee'])
            ->whereIn('target_role', $targetRoles)
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function postMessage(Ticket $ticket, User $sender, string $message): TicketMessage
    {
        $created = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $sender->id,
            'message' => $message,
        ]);

        if ($sender->id !== $ticket->student_id && $ticket->status !== TicketStatus::Closed) {
            $ticket->update([
                'status' => TicketStatus::Answered->value,
                'assigned_to_user_id' => $ticket->assigned_to_user_id ?? $sender->id,
            ]);
        }

        return $created;
    }

    public function updateStatus(Ticket $ticket, User $actor, TicketStatus $status): Ticket
    {
        $ticket->update([
            'status' => $status->value,
            'assigned_to_user_id' => $ticket->assigned_to_user_id ?? $actor->id,
        ]);

        return $ticket->fresh();
    }

    /**
     * @return array<int, string>
     */
    private function targetRolesForStaff(User $user): array
    {
        $roles = [];
        if ($user->hasRole(TicketTargetRole::Admin->value)) {
            $roles[] = TicketTargetRole::Admin->value;
        }
        if ($user->hasRole(TicketTargetRole::Counselor->value)) {
            $roles[] = TicketTargetRole::Counselor->value;
        }

        return $roles ?: ['__none__'];
    }
}
