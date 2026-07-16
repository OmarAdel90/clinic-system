<?php

namespace Modules\Auth\Support;

use Illuminate\Contracts\Auth\Authenticatable;

class SeededAdmin
{
    public const EMAIL = 'super@clinic.com';

    public static function email(): string
    {
        return self::EMAIL;
    }

    public static function matches(?Authenticatable $user): bool
    {
        return $user?->email === self::EMAIL;
    }
}
