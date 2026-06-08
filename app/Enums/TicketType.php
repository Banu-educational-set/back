<?php

namespace App\Enums;

enum TicketType: string
{
    case Ticket = 'ticket';
    case Service = 'service';
    case Advise = 'advise';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
