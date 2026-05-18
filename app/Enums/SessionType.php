<?php

namespace App\Enums;

enum SessionType: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Content = 'content';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
