<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
