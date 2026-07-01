<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserAccountStatus: int
{
    use HasValues;

    case Disabled = 0;
    case Active = 1;
}
