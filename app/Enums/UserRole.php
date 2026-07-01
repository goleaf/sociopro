<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserRole: string
{
    use HasValues;

    case Admin = 'admin';
    case General = 'general';
    case Member = 'member';
}
