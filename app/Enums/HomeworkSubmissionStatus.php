<?php

namespace App\Enums;

enum HomeworkSubmissionStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Denied = 'denied';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
