<?php

namespace App\Enums;

enum TicketTargetRole: string
{
    case Admin = 'admin';
    case Counselor = 'counselor';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
