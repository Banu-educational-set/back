<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Enums\TicketType;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->id === $ticket->student_id) {
            return true;
        }

        return $this->staffCanHandle($user, $ticket);
    }

    public function postMessage(User $user, Ticket $ticket): bool
    {
        if ($user->id === $ticket->student_id) {
            return true;
        }

        return $this->staffCanHandle($user, $ticket);
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $this->staffCanHandle($user, $ticket);
    }

    private function staffCanHandle(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole(RoleName::Admin->value)) {
            return true;
        }

        if ($user->hasRole(RoleName::Counselor->value)) {
            return $ticket->type === TicketType::Advise;
        }

        return false;
    }
}
