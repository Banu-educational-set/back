<?php

namespace App\Enums;

enum MissionaryRequestStatus: string
{
    case Pending = 'pending';
    case Seen = 'seen';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }

    /**
     * Statuses a missionary may transition to.
     *
     * @return array<int, string>
     */
    public static function missionaryAssignable(): array
    {
        return [self::Seen->value, self::Accepted->value, self::Rejected->value];
    }
}
