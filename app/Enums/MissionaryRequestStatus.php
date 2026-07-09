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
     * Current statuses a missionary is allowed to transition FROM.
     *
     * @return array<int, string>
     */
    public static function missionarySources(): array
    {
        return [self::Pending->value, self::Seen->value];
    }

    /**
     * Statuses a missionary may transition TO. Admins are not restricted and
     * may set any of values().
     *
     * @return array<int, string>
     */
    public static function missionaryTargets(): array
    {
        return [self::Accepted->value, self::Rejected->value];
    }
}
