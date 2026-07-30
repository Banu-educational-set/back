<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Media;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function createForStudent(User $student, array $data): Ticket
    {
        $mediaIds = Arr::get($data, 'media_ids', []);

        return DB::transaction(function () use ($student, $data, $mediaIds) {
            $ticket = Ticket::create([
                'student_id' => $student->id,
                'type' => $data['type'],
                'category' => $data['category'] ?? null,
                'priority' => $data['priority'] ?? TicketPriority::Normal->value,
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

            $this->attachMedia($ticket, $mediaIds, $student);

            return $ticket->load(['messages', 'media']);
        });
    }

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

    public function listForStudent(User $student, ?TicketType $type = null, ?TicketPriority $priority = null, ?TicketStatus $status = null): LengthAwarePaginator
    {
        return Ticket::query()
            ->with('media')
            ->where('student_id', $student->id)
            ->when($type, fn ($q, $t) => $q->where('type', $t->value))
            ->when($priority, fn ($q, $p) => $q->where('priority', $p->value))
            ->when($status, fn ($q, $s) => $q->where('status', $s->value))
            ->orderByDesc('id')
            ->paginate(20);
    }

    /**
     * Admin sees every ticket type; counselor only sees `advise`. The
     * optional $type lets admins narrow by a specific type; for counselors
     * the type is locked to `advise` regardless of the input.
     */
    /**
     * @param  array<string, mixed>  $filters  user_name, from_date, to_date
     */
    public function listForStaff(User $staff, ?TicketType $type = null, ?TicketPriority $priority = null, ?TicketStatus $status = null, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $isAdmin = $staff->hasRole(RoleName::Admin->value);
        $isCounselor = $staff->hasRole(RoleName::Counselor->value);

        $query = Ticket::query()->with(['student', 'assignee', 'media']);

        if ($isAdmin) {
            if ($type) {
                $query->where('type', $type->value);
            }
        } elseif ($isCounselor) {
            $query->where('type', TicketType::Advise->value);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($priority) {
            $query->where('priority', $priority->value);
        }

        if ($status) {
            $query->where('status', $status->value);
        }

        $this->applyTicketFilters($query, $filters);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Apply the shared owner-name / creation-date filters to a ticket query.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applyTicketFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        $query
            ->when($filters['user_name'] ?? null, fn ($q, $v) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', "%{$v}%")))
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    /**
     * Return a per-status count summary for a given query scope.
     * Shape: { open_tickets, answered_tickets, closed_tickets, all }.
     *
     * @return array<string, int>
     */
    public function statsForStudent(User $student, ?TicketType $type = null): array
    {
        return $this->statsFor(
            Ticket::query()
                ->where('student_id', $student->id)
                ->when($type, fn ($q, $t) => $q->where('type', $t->value))
        );
    }

    /**
     * Stats for the staff scope. Admin sees all types; counselor only `advise`.
     *
     * @return array<string, int>
     */
    /**
     * @param  array<string, mixed>  $filters  user_name, from_date, to_date
     */
    public function statsForStaff(User $staff, ?TicketType $type = null, array $filters = []): array
    {
        $isAdmin = $staff->hasRole(RoleName::Admin->value);
        $isCounselor = $staff->hasRole(RoleName::Counselor->value);

        $query = Ticket::query();

        if ($isAdmin) {
            if ($type) {
                $query->where('type', $type->value);
            }
        } elseif ($isCounselor) {
            $query->where('type', TicketType::Advise->value);
        } else {
            $query->whereRaw('1 = 0');
        }

        $this->applyTicketFilters($query, $filters);

        return $this->statsFor($query);
    }

    /**
     * @return array<string, int>
     */
    private function statsFor(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $out = [
            'open_tickets' => (int) ($byStatus[TicketStatus::Open->value] ?? 0),
            'answered_tickets' => (int) ($byStatus[TicketStatus::Answered->value] ?? 0),
            'closed_tickets' => (int) ($byStatus[TicketStatus::Closed->value] ?? 0),
        ];
        $out['all'] = array_sum($out);

        return $out;
    }

    public function postMessage(Ticket $ticket, User $sender, ?string $message, array $mediaIds = []): TicketMessage
    {
        return DB::transaction(function () use ($ticket, $sender, $message, $mediaIds) {
            $created = TicketMessage::create([
                'ticket_id' => $ticket->id,
                'sender_id' => $sender->id,
                'message' => $message,
            ]);

            $this->attachMedia($created, $mediaIds, $sender);

            if ($sender->id !== $ticket->student_id && $ticket->status !== TicketStatus::Closed) {
                $ticket->update([
                    'status' => TicketStatus::Answered->value,
                    'assigned_to_user_id' => $ticket->assigned_to_user_id ?? $sender->id,
                ]);
            }

            return $created->load('media');
        });
    }

    public function updateStatus(Ticket $ticket, User $actor, TicketStatus $status): Ticket
    {
        $ticket->update([
            'status' => $status->value,
            'assigned_to_user_id' => $ticket->assigned_to_user_id ?? $actor->id,
        ]);

        return $ticket->fresh();
    }
}
