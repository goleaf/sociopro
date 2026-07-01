<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MembershipRole: string
{
    use HasValues;

    case Admin = 'admin';
    case General = 'general';
}
