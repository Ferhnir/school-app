<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case PARENT = 'parent';

    public static function all(): array
    {
        return self::cases();
    }
}
