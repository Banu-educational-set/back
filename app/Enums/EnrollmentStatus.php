<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
