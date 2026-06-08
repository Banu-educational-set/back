<?php

namespace App\Enums;

enum MarriageStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
