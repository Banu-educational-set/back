<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
