<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
