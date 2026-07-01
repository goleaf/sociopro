<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AccountActivationStatus: string
{
    use HasValues;

    case Pending = 'pending';
}
