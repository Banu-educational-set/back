<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
    case Missionary = 'missionary';
    case Counselor = 'counselor';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
