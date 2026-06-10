<?php

namespace App\Enums;

enum UserStatus: string
{
    /** Just registered. Phone not yet OTP-verified. */
    case Pending = 'pending';

    /** Phone OTP-verified. Awaiting admin approval. */
    case Verified = 'verified';

    /** Admin-approved. Full access to the system. */
    case Approved = 'approved';

    /** Admin-blocked. No access. */
    case Blocked = 'blocked';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
