<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case ADMINISTRATOR = 'administrator';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}